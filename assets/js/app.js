/**
 * CommUnity — Application JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

    // ---------- Mobile menu toggle ----------
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
                if (!sidebar.contains(e.target) && !menuToggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
    }

    // ---------- Auto-dismiss flash alerts ----------
    const flashAlert = document.getElementById('flashAlert');
    if (flashAlert) {
        setTimeout(function () {
            flashAlert.style.transition = 'opacity 0.5s';
            flashAlert.style.opacity = '0';
            setTimeout(function () { flashAlert.remove(); }, 500);
        }, 5000);
    }

    // ---------- Notification bell polling (every 15 seconds) ----------
    const bellBadge = document.getElementById('bellBadge');
    if (bellBadge) {
        setInterval(function () {
            fetch(BASE_URL + 'api/notifications.php?action=count')
                .then(r => r.json())
                .then(data => {
                    if (data.count > 0) {
                        bellBadge.textContent = data.count;
                        bellBadge.style.display = 'flex';
                    } else {
                        bellBadge.style.display = 'none';
                    }
                })
                .catch(() => {});
        }, 15000);
    }

    // ---------- Resident search (Watchman) ----------
    const searchInput = document.getElementById('residentSearch');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                performResidentSearch();
            }, 300);
        });
    }

    const blockFilter = document.getElementById('blockFilter');
    if (blockFilter) {
        blockFilter.addEventListener('change', performResidentSearch);
    }

    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', performResidentSearch);
    }

    function performResidentSearch() {
        const query = document.getElementById('residentSearch')?.value || '';
        const block = document.getElementById('blockFilter')?.value || '';
        const status = document.getElementById('statusFilter')?.value || '';
        const tbody = document.getElementById('residentTableBody');
        const emptyState = document.getElementById('residentEmptyState');

        if (!tbody) return;

        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Searching...</td></tr>';

        const params = new URLSearchParams({ q: query, block: block, status: status });

        fetch(BASE_URL + 'api/residents.php?' + params.toString())
            .then(r => r.json())
            .then(data => {
                if (data.residents && data.residents.length > 0) {
                    tbody.innerHTML = data.residents.map(r => `
                        <tr>
                            <td>
                                <div class="d-flex align-center gap-2">
                                    <img src="${r.profile_image || BASE_URL + 'assets/images/default-avatar.png'}" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
                                    <strong>${escapeHtml(r.name)}</strong>
                                </div>
                            </td>
                            <td><span class="badge badge-info">${escapeHtml(r.block || '—')}</span></td>
                            <td>${escapeHtml(r.door_number || '—')}</td>
                            <td>${availabilityBadge(r.availability)}</td>
                            <td>
                                <a href="${BASE_URL}watchman/residents.php?view=${r.id}" class="btn btn-sm btn-outline">View</a>
                            </td>
                        </tr>
                    `).join('');
                    if (emptyState) emptyState.style.display = 'none';
                } else {
                    tbody.innerHTML = '';
                    if (emptyState) emptyState.style.display = 'block';
                }
            })
            .catch(() => {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading residents.</td></tr>';
            });
    }

    // ---------- Visitor PIN lookup ----------
    const pinInput = document.getElementById('visitorPinInput');
    const pinSearchBtn = document.getElementById('pinSearchBtn');
    const pinResult = document.getElementById('pinResult');

    if (pinSearchBtn && pinInput) {
        pinSearchBtn.addEventListener('click', lookupPin);
        pinInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); lookupPin(); }
        });
    }

    function lookupPin() {
        const pin = pinInput.value.trim();
        if (!pin || pin.length !== 6) {
            pinResult.innerHTML = '<div class="alert alert-danger">Please enter a valid 6-digit PIN.</div>';
            return;
        }

        pinResult.innerHTML = '<div class="alert alert-info">Looking up PIN...</div>';

        fetch(BASE_URL + 'api/visitors.php?action=lookup&pin=' + encodeURIComponent(pin))
            .then(r => r.json())
            .then(data => {
                if (data.success && data.visitor) {
                    const v = data.visitor;
                    pinResult.innerHTML = `
                        <div class="card mt-2">
                            <div class="card-body">
                                <div class="d-flex justify-between align-center mb-2">
                                    <h3>${escapeHtml(v.visitor_name)}</h3>
                                    ${visitorStatusBadge(v.status)}
                                </div>
                                <div class="form-row">
                                    <div><strong>Resident:</strong> ${escapeHtml(v.resident_name)}</div>
                                    <div><strong>Block:</strong> ${escapeHtml(v.block)} | <strong>Door:</strong> ${escapeHtml(v.door_number)}</div>
                                    <div><strong>Purpose:</strong> ${escapeHtml(v.purpose || '—')}</div>
                                    <div><strong>Type:</strong> ${escapeHtml(v.visitor_type)}</div>
                                    <div><strong>Expected:</strong> ${formatDate(v.expected_date)} ${formatTime(v.expected_time)}</div>
                                </div>
                                <div class="d-flex gap-2 mt-2 flex-wrap">
                                    <button class="btn btn-success" onclick="markArrived(${v.id})">Visitor Arrived</button>
                                    <button class="btn btn-primary" onclick="notifyResident(${v.id})">Notify Resident</button>
                                    ${v.status === 'Inside' ? `<button class="btn btn-danger" onclick="recordExit(${v.id})">Record Exit</button>` : ''}
                                    ${v.status === 'Approved' ? `<button class="btn btn-success" onclick="recordEntry(${v.id})">Allow Entry</button>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                } else {
                    pinResult.innerHTML = `<div class="alert alert-danger">${escapeHtml(data.message || 'Visitor PIN is invalid or expired.')}</div>`;
                }
            })
            .catch(() => {
                pinResult.innerHTML = '<div class="alert alert-danger">Error looking up PIN.</div>';
            });
    }

    // Expose functions globally for inline onclick
    window.markArrived = function (visitorId) {
        fetch(BASE_URL + 'api/visitors.php?action=arrived', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'visitor_id=' + visitorId
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                setFlashMessage('success', data.message);
                location.reload();
            } else {
                setFlashMessage('danger', data.message);
                location.reload();
            }
        });
    };

    window.notifyResident = function (visitorId) {
        fetch(BASE_URL + 'api/visitors.php?action=notify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'visitor_id=' + visitorId
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                setFlashMessage('success', data.message);
                location.reload();
            } else {
                setFlashMessage('danger', data.message);
                location.reload();
            }
        });
    };

    window.recordExit = function (visitorId) {
        fetch(BASE_URL + 'api/visitors.php?action=exit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'visitor_id=' + visitorId
        })
        .then(r => r.json())
        .then(data => {
            setFlashMessage(data.success ? 'success' : 'danger', data.message);
            location.reload();
        });
    };

    window.recordEntry = function (visitorId) {
        fetch(BASE_URL + 'api/visitors.php?action=entry', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'visitor_id=' + visitorId
        })
        .then(r => r.json())
        .then(data => {
            setFlashMessage(data.success ? 'success' : 'danger', data.message);
            location.reload();
        });
    };

    // ---------- Delivery: check resident availability ----------
    const deliveryResidentSelect = document.getElementById('deliveryResident');
    const availabilityAlert = document.getElementById('availabilityAlert');

    if (deliveryResidentSelect) {
        deliveryResidentSelect.addEventListener('change', function () {
            const residentId = this.value;
            if (!residentId) {
                availabilityAlert.style.display = 'none';
                return;
            }

            fetch(BASE_URL + 'api/residents.php?action=availability&resident_id=' + residentId)
                .then(r => r.json())
                .then(data => {
                    if (data.availability) {
                        availabilityAlert.style.display = 'block';
                        if (data.availability === 'Available') {
                            availabilityAlert.className = 'alert alert-success';
                            availabilityAlert.innerHTML = '<strong>Resident Available</strong> — The watchman can handle the delivery normally.';
                        } else if (data.availability === 'Inside') {
                            availabilityAlert.className = 'alert alert-info';
                            availabilityAlert.innerHTML = '<strong>Resident Inside</strong> — The resident is inside the community.';
                        } else {
                            availabilityAlert.className = 'alert alert-warning';
                            availabilityAlert.innerHTML = '<strong>Resident Not Available</strong> — Click "Notify Resident" after saving the delivery.';
                        }
                    }
                })
                .catch(() => {
                    availabilityAlert.style.display = 'none';
                });
        });
    }

    // ---------- Delivery notify button ----------
    window.notifyDeliveryResident = function (deliveryId) {
        fetch(BASE_URL + 'api/deliveries.php?action=notify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'delivery_id=' + deliveryId
        })
        .then(r => r.json())
        .then(data => {
            setFlashMessage(data.success ? 'success' : 'danger', data.message);
            location.reload();
        });
    };

    // ---------- Resource search ----------
    const resourceSearchInput = document.getElementById('resourceSearch');
    if (resourceSearchInput) {
        let debounceTimer;
        resourceSearchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(performResourceSearch, 300);
        });
    }

    ['resourceCategory', 'resourceSharingType', 'resourceAvailability', 'resourcePrice'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', performResourceSearch);
    });

    function performResourceSearch() {
        const query = document.getElementById('resourceSearch')?.value || '';
        const category = document.getElementById('resourceCategory')?.value || '';
        const sharingType = document.getElementById('resourceSharingType')?.value || '';
        const availability = document.getElementById('resourceAvailability')?.value || '';
        const price = document.getElementById('resourcePrice')?.value || '';
        const container = document.getElementById('resourceResults');

        if (!container) return;

        container.innerHTML = '<div class="empty-state"><p>Searching...</p></div>';

        const params = new URLSearchParams({
            q: query, category: category, sharing_type: sharingType,
            availability: availability, price: price
        });

        fetch(BASE_URL + 'api/resources.php?' + params.toString())
            .then(r => r.json())
            .then(data => {
                if (data.resources && data.resources.length > 0) {
                    container.innerHTML = '<div class="grid-3">' + data.resources.map(r => `
                        <div class="resource-card">
                            <img src="${r.image || BASE_URL + 'assets/images/default-resource.png'}" alt="${escapeHtml(r.item_name)}" class="resource-card-image" onerror="this.src='${BASE_URL}assets/images/default-resource.png'">
                            <div class="resource-card-body">
                                <h4>${escapeHtml(r.item_name)}</h4>
                                <p>${escapeHtml(r.description || '')}</p>
                                <div class="resource-card-meta">
                                    <span class="resource-card-price ${r.price == 0 ? 'free' : ''}">${r.price > 0 ? '₹' + parseFloat(r.price).toFixed(0) : 'Free'}</span>
                                    ${resourceStatusBadge(r.status)}
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge badge-info">${escapeHtml(r.sharing_type)}</span>
                                    <span class="badge badge-secondary">${escapeHtml(r.condition || '—')}</span>
                                </div>
                                <div class="mt-2 d-flex justify-between align-center">
                                    <small class="text-muted">${escapeHtml(r.owner_name)} | ${escapeHtml(r.block || '')}</small>
                                    ${r.can_request ? `<button class="btn btn-sm btn-primary" onclick="requestResource(${r.id})">Request Item</button>` : ''}
                                </div>
                            </div>
                        </div>
                    `).join('') + '</div>';
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                            <h4>No items found</h4>
                            <p>Try adjusting your search or filters.</p>
                        </div>
                    `;
                }
            })
            .catch(() => {
                container.innerHTML = '<div class="empty-state"><p class="text-danger">Error loading resources.</p></div>';
            });
    }

    window.requestResource = function (resourceId) {
        const message = prompt('Add a message for the owner (optional):', '');
        if (message === null) return;

        fetch(BASE_URL + 'api/resources.php?action=request', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'resource_id=' + resourceId + '&message=' + encodeURIComponent(message)
        })
        .then(r => r.json())
        .then(data => {
            setFlashMessage(data.success ? 'success' : 'danger', data.message);
        });
    };

    // ---------- Skill search ----------
    const skillSearchInput = document.getElementById('skillSearch');
    if (skillSearchInput) {
        let debounceTimer;
        skillSearchInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(performSkillSearch, 300);
        });
    }

    const skillCategoryFilter = document.getElementById('skillCategoryFilter');
    if (skillCategoryFilter) {
        skillCategoryFilter.addEventListener('change', performSkillSearch);
    }

    function performSkillSearch() {
        const query = document.getElementById('skillSearch')?.value || '';
        const category = document.getElementById('skillCategoryFilter')?.value || '';
        const container = document.getElementById('skillResults');

        if (!container) return;

        container.innerHTML = '<div class="empty-state"><p>Searching...</p></div>';

        const params = new URLSearchParams({ q: query, category: category });

        fetch(BASE_URL + 'api/skills.php?' + params.toString())
            .then(r => r.json())
            .then(data => {
                if (data.skills && data.skills.length > 0) {
                    container.innerHTML = '<div class="grid-3">' + data.skills.map(s => `
                        <div class="skill-card">
                            <div class="skill-card-header">
                                <h4>${escapeHtml(s.skill_name)}</h4>
                                <span class="category">${escapeHtml(s.category)}</span>
                            </div>
                            <p>${escapeHtml(s.description || '')}</p>
                            <div class="skill-card-details">
                                <span>💰 ₹${parseFloat(s.expected_cost || 0).toFixed(0)}</span>
                                <span>⏱ ${escapeHtml(s.response_time || '—')}</span>
                                <span>📅 ${escapeHtml(s.availability || '—')}</span>
                                <span>👤 ${escapeHtml(s.resident_name)}</span>
                            </div>
                            <small class="text-muted">${escapeHtml(s.block || '')} ${escapeHtml(s.door_number || '')}</small>
                            <div class="mt-2">
                                ${s.can_request ? `<button class="btn btn-sm btn-primary" onclick="requestHelp(${s.id})">Request Help</button>` : ''}
                            </div>
                        </div>
                    `).join('') + '</div>';
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                            <h4>No skills found</h4>
                            <p>Try a different search term or category.</p>
                        </div>
                    `;
                }
            })
            .catch(() => {
                container.innerHTML = '<div class="empty-state"><p class="text-danger">Error loading skills.</p></div>';
            });
    }

    window.requestHelp = function (skillId) {
        const desc = prompt('Describe your problem:', '');
        if (!desc) return;

        const date = prompt('Preferred date (YYYY-MM-DD):', '');
        const time = prompt('Preferred time (HH:MM):', '');

        fetch(BASE_URL + 'api/help_requests.php?action=request', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'skill_id=' + skillId + '&description=' + encodeURIComponent(desc) + '&preferred_date=' + encodeURIComponent(date) + '&preferred_time=' + encodeURIComponent(time)
        })
        .then(r => r.json())
        .then(data => {
            setFlashMessage(data.success ? 'success' : 'danger', data.message);
        });
    };

    // ---------- Mark notification as read ----------
    window.markNotificationRead = function (notifId, event) {
        event.preventDefault();
        fetch(BASE_URL + 'api/notifications.php?action=mark_read&id=' + notifId)
            .then(r => r.json())
            .then(() => {
                const item = document.getElementById('notif-' + notifId);
                if (item) item.classList.remove('unread');
            });
    };

    window.markAllRead = function () {
        fetch(BASE_URL + 'api/notifications.php?action=mark_all_read')
            .then(r => r.json())
            .then(() => {
                document.querySelectorAll('.notification-item.unread').forEach(el => el.classList.remove('unread'));
                const badge = document.getElementById('bellBadge');
                if (badge) badge.style.display = 'none';
            });
    };

    // ---------- Approve / Reject visitor from notification ----------
    window.approveVisitor = function (visitorId) {
        fetch(BASE_URL + 'api/visitors.php?action=approve', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'visitor_id=' + visitorId
        })
        .then(r => r.json())
        .then(data => {
            setFlashMessage(data.success ? 'success' : 'danger', data.message);
            setTimeout(() => location.reload(), 1000);
        });
    };

    window.rejectVisitor = function (visitorId) {
        fetch(BASE_URL + 'api/visitors.php?action=reject', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'visitor_id=' + visitorId
        })
        .then(r => r.json())
        .then(data => {
            setFlashMessage(data.success ? 'success' : 'danger', data.message);
            setTimeout(() => location.reload(), 1000);
        });
    };

    // ---------- Help request actions ----------
    window.acceptHelpRequest = function (requestId) {
        fetch(BASE_URL + 'api/help_requests.php?action=accept', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'request_id=' + requestId
        })
        .then(r => r.json())
        .then(data => {
            setFlashMessage(data.success ? 'success' : 'danger', data.message);
            setTimeout(() => location.reload(), 1000);
        });
    };

    window.completeHelpRequest = function (requestId) {
        fetch(BASE_URL + 'api/help_requests.php?action=complete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'request_id=' + requestId
        })
        .then(r => r.json())
        .then(data => {
            setFlashMessage(data.success ? 'success' : 'danger', data.message);
            setTimeout(() => location.reload(), 1000);
        });
    };

    window.cancelHelpRequest = function (requestId) {
        if (!confirm('Are you sure you want to cancel this request?')) return;
        fetch(BASE_URL + 'api/help_requests.php?action=cancel', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'request_id=' + requestId
        })
        .then(r => r.json())
        .then(data => {
            setFlashMessage(data.success ? 'success' : 'danger', data.message);
            setTimeout(() => location.reload(), 1000);
        });
    };

    // ---------- Resource request actions ----------
    window.acceptResourceRequest = function (requestId) {
        fetch(BASE_URL + 'api/resources.php?action=accept_request', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'request_id=' + requestId
        })
        .then(r => r.json())
        .then(data => {
            setFlashMessage(data.success ? 'success' : 'danger', data.message);
            setTimeout(() => location.reload(), 1000);
        });
    };

    window.rejectResourceRequest = function (requestId) {
        fetch(BASE_URL + 'api/resources.php?action=reject_request', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'request_id=' + requestId
        })
        .then(r => r.json())
        .then(data => {
            setFlashMessage(data.success ? 'success' : 'danger', data.message);
            setTimeout(() => location.reload(), 1000);
        });
    };

    window.completeResourceRequest = function (requestId) {
        fetch(BASE_URL + 'api/resources.php?action=complete_request', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'request_id=' + requestId
        })
        .then(r => r.json())
        .then(data => {
            setFlashMessage(data.success ? 'success' : 'danger', data.message);
            setTimeout(() => location.reload(), 1000);
        });
    };

    // ---------- Delivery collected ----------
    window.markDeliveryCollected = function (deliveryId) {
        fetch(BASE_URL + 'api/deliveries.php?action=collect', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'delivery_id=' + deliveryId
        })
        .then(r => r.json())
        .then(data => {
            setFlashMessage(data.success ? 'success' : 'danger', data.message);
            setTimeout(() => location.reload(), 1000);
        });
    };

    // ---------- Utility functions ----------
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function setFlashMessage(type, message) {
        const container = document.getElementById('flashContainer');
        if (!container) {
            // Create one if it doesn't exist
            const main = document.querySelector('.page-content');
            if (main) {
                const div = document.createElement('div');
                div.id = 'flashContainer';
                div.innerHTML = `<div class="alert alert-${type}"><span>${escapeHtml(message)}</span></div>`;
                main.insertBefore(div, main.firstChild);
            }
            return;
        }
        container.innerHTML = `<div class="alert alert-${type} alert-dismissible"><span>${escapeHtml(message)}</span><button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button></div>`;
    }

    function availabilityBadge(status) {
        const cls = status === 'Available' ? 'badge-success' : status === 'Inside' ? 'badge-primary' : 'badge-danger';
        return `<span class="badge ${cls}">${escapeHtml(status)}</span>`;
    }

    function visitorStatusBadge(status) {
        const cls = {
            'Expected': 'badge-secondary', 'Arrived': 'badge-warning', 'Approved': 'badge-info',
            'Rejected': 'badge-danger', 'Inside': 'badge-primary', 'Exited': 'badge-success'
        }[status] || 'badge-secondary';
        return `<span class="badge ${cls}">${escapeHtml(status)}</span>`;
    }

    function resourceStatusBadge(status) {
        const cls = {
            'Available': 'badge-success', 'Request Pending': 'badge-warning', 'Reserved': 'badge-info',
            'Borrowed': 'badge-primary', 'Rented': 'badge-primary', 'Sold': 'badge-success',
            'Donated': 'badge-success', 'Exchanged': 'badge-success', 'Unavailable': 'badge-danger'
        }[status] || 'badge-secondary';
        return `<span class="badge ${cls}">${escapeHtml(status)}</span>`;
    }

    function formatDate(d) {
        if (!d) return '—';
        const date = new Date(d);
        return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    }

    function formatTime(t) {
        if (!t) return '';
        const [h, m] = t.split(':');
        const hour = parseInt(h);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const hr = hour % 12 || 12;
        return hr + ':' + m + ' ' + ampm;
    }

    // Make BASE_URL available globally
    window.BASE_URL = document.body.getAttribute('data-base-url') || '/community/';
});
