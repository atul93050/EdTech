jQuery(function($) {
    'use strict';

    const config = window.EDTECH_AJAX || window.EDTECH_THEME || {};
    const UI = window.EDTECH_UI || {};
    const nonce = config.nonce || '';
    let optionCache = null;

    function notify(message, type) {
        if (UI.showSuccess && type === 'success') {
            UI.showSuccess(message);
            return;
        }
        if (UI.showError && type === 'error') {
            UI.showError(message);
            return;
        }
        if (UI.showWarning && type === 'warning') {
            UI.showWarning(message);
            return;
        }
        if (UI.showInfo) {
            UI.showInfo(message);
            return;
        }
        console.log(message);
    }

    function confirmAction(options) {
        if (UI.showConfirmModal) {
            return UI.showConfirmModal(options);
        }
        return Promise.resolve(true);
    }

    function requestAction(action, data, options = {}) {
        const loader = UI.showLoader ? UI.showLoader(options.loading || 'Working...') : null;

        return $.ajax({
            url: config.ajax_url,
            type: 'POST',
            data: Object.assign({ action, nonce }, data || {})
        }).done(function(response) {
            if (response && response.success) {
                notify(options.success || (response.data && response.data.message) || 'Done.', 'success');
                if (typeof options.onSuccess === 'function') {
                    options.onSuccess(response);
                }
                if (options.reload) {
                    window.setTimeout(function() {
                        window.location.reload();
                    }, 450);
                }
                return;
            }

            notify((response && response.data && response.data.message) || options.error || 'Something went wrong.', 'error');
        }).fail(function(xhr) {
            notify((xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || options.error || 'Request failed.', 'error');
        }).always(function() {
            if (loader && loader.close) {
                loader.close();
            }
        });
    }

    function formValues(form) {
        const values = {};
        const data = new FormData(form);

        data.forEach(function(value, key) {
            if (key.slice(-2) === '[]') {
                key = key.slice(0, -2);
            }
            if (values[key] !== undefined) {
                if (!Array.isArray(values[key])) {
                    values[key] = [values[key]];
                }
                values[key].push(value);
            } else {
                values[key] = value;
            }
        });

        return values;
    }

    function generateSlug(value) {
        return String(value || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }

    function loadAdminOptions() {
        if (optionCache) {
            populateDynamicSelects(optionCache);
            return $.Deferred().resolve(optionCache).promise();
        }

        $('[data-source="categories"], [data-source="teachers"]').each(function() {
            if (!this.options.length || this.options[0].text.indexOf('Loading') === -1) {
                return;
            }
            this.innerHTML = '<option value="">Loading...</option>';
        });

        return requestAction('edtech_get_admin_options', {}, {
            loading: 'Loading LMS options...',
            success: 'Options loaded.',
            onSuccess: function(response) {
                optionCache = response.data || {};
                populateDynamicSelects(optionCache);
            }
        });
    }

    function populateDynamicSelects(data) {
        $('[data-source="categories"]').each(function() {
            const selected = $(this).data('selected') || $(this).val();
            const options = ['<option value="">Uncategorized</option>'];
            (data.categories || []).forEach(function(category) {
                options.push('<option value="' + escapeAttr(category.id) + '">' + escapeHtml(category.name) + '</option>');
            });
            this.innerHTML = options.join('');
            if (selected) {
                $(this).val(String(selected));
            }
        });

        $('[data-source="teachers"]').each(function() {
            const selected = $(this).data('selected') || $(this).val();
            const options = [];
            (data.teachers || []).forEach(function(teacher) {
                options.push('<option value="' + escapeAttr(teacher.user_id) + '">' + escapeHtml(teacher.full_name || teacher.user_email || ('Teacher #' + teacher.user_id)) + '</option>');
            });
            if (!options.length) {
                options.push('<option value="" disabled>No approved teachers available</option>');
            }
            this.innerHTML = options.join('');
            if (selected) {
                $(this).val(Array.isArray(selected) ? selected.map(String) : String(selected));
            }
        });

        enhanceSearchableSelects();
    }

    function enhanceSearchableSelects() {
        $('.edtech-searchable-select').each(function() {
            const select = $(this);
            if (select.prev('.edtech-search-field').length) {
                return;
            }

            const search = $('<input type="search" class="form-control edtech-search-field" placeholder="Search options">');
            select.before(search);
        });
    }

    $(document).on('input', '.edtech-search-field', function() {
        const query = $(this).val().toLowerCase();
        const select = $(this).next('select')[0];
        if (!select) {
            return;
        }

        Array.from(select.options).forEach(function(option) {
            option.hidden = query && option.text.toLowerCase().indexOf(query) === -1 && option.value !== '';
        });
    });

    function openSubjectModal(payload, mode) {
        const modalEl = document.getElementById('subjectModal');
        const form = document.getElementById('subjectForm');
        if (!modalEl || !form) {
            window.location.href = addViewToUrl('subjects');
            return;
        }

        form.reset();
        $(form).find('[name="subject_id"]').val(payload && payload.id ? payload.id : '');
        $(form).find('[name="title"]').val(payload && payload.title ? payload.title : '');
        $(form).find('[name="slug"]').val(payload && payload.slug ? payload.slug : '');
        $(form).find('[name="thumbnail"]').val(payload && payload.thumbnail ? payload.thumbnail : '');
        $(form).find('[name="icon"]').val(payload && payload.icon ? payload.icon : '');
        $(form).find('[name="description"]').val(payload && payload.description ? payload.description : '');
        $(form).find('[name="status"]').val(payload && payload.status ? payload.status : 'active');
        $(form).find('[data-source="categories"]').data('selected', payload && payload.category_id ? payload.category_id : '');
        $(form).find('[data-source="teachers"]').data('selected', payload && payload.teacher_ids && payload.teacher_ids.length ? payload.teacher_ids : []);

        $('#subjectModal .modal-title').text(mode === 'view' ? 'View Subject' : (payload && payload.id ? 'Edit Subject' : 'Create Subject'));
        $('#subjectModal [data-save-subject]').toggleClass('d-none', mode === 'view');
        $(form).find('input, textarea, select').prop('disabled', mode === 'view');

        loadAdminOptions().always(function() {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        });
    }

    function openCategoryModal(payload) {
        const modalEl = document.getElementById('categoryModal');
        const form = document.getElementById('categoryForm');
        if (!modalEl || !form) {
            window.location.href = addViewToUrl('categories');
            return;
        }

        form.reset();
        $(form).find('[name="category_id"]').val(payload && payload.id ? payload.id : '');
        $(form).find('[name="name"]').val(payload && payload.name ? payload.name : '');
        $(form).find('[name="slug"]').val(payload && payload.slug ? payload.slug : '');
        $(form).find('[name="status"]').val(payload && payload.status ? payload.status : 'active');
        $('#categoryModal .modal-title').text(payload && payload.id ? 'Edit Category' : 'Create Category');

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }

    $(document).on('click', '[data-edtech-subject-new]', function(event) {
        event.preventDefault();
        event.stopPropagation();
        openSubjectModal(null, 'edit');
    });

    $(document).on('click', '[data-edtech-category-new]', function(event) {
        event.preventDefault();
        event.stopPropagation();
        openCategoryModal(null);
    });

    $(document).on('click', '[data-subject-edit], [data-subject-view]', function() {
        const raw = $(this).attr('data-subject-edit') || $(this).attr('data-subject-view');
        const payload = parseJson(raw);
        openSubjectModal(payload, $(this).is('[data-subject-view]') ? 'view' : 'edit');
    });

    $(document).on('click', '[data-category-edit]', function() {
        openCategoryModal(parseJson($(this).attr('data-category-edit')));
    });

    $(document).on('click', '[data-save-subject]', function() {
        const form = document.getElementById('subjectForm');
        if (!form) {
            return notify('Subject form not found.', 'error');
        }

        const values = formValues(form);
        values.slug = values.slug || generateSlug(values.title);

        if (!values.title) {
            return notify('Subject title is required.', 'error');
        }

        requestAction(values.subject_id ? 'edtech_update_subject' : 'edtech_create_subject', values, {
            loading: 'Saving subject...',
            success: values.subject_id ? 'Subject updated.' : 'Subject created.',
            reload: true
        });
    });

    $(document).on('click', '[data-save-category]', function() {
        const form = document.getElementById('categoryForm');
        if (!form) {
            return notify('Category form not found.', 'error');
        }

        const values = formValues(form);
        values.slug = values.slug || generateSlug(values.name);

        if (!values.name) {
            return notify('Category name is required.', 'error');
        }

        requestAction(values.category_id ? 'edtech_update_category' : 'edtech_create_category', values, {
            loading: 'Saving category...',
            success: values.category_id ? 'Category updated.' : 'Category created.',
            reload: true
        });
    });

    $(document).on('click', '[data-subject-delete]', function() {
        const subjectId = $(this).data('subject-delete');
        const title = $(this).data('title') || 'this subject';

        confirmAction({
            title: 'Delete subject?',
            message: 'Delete "' + title + '" and remove its teacher/student relationships?',
            confirmText: 'Delete',
            variant: 'danger'
        }).then(function(confirmed) {
            if (!confirmed) {
                return;
            }

            requestAction('edtech_delete_subject', { subject_id: subjectId }, {
                loading: 'Deleting subject...',
                success: 'Subject deleted.',
                reload: true
            });
        });
    });

    $(document).on('click', '[data-category-delete]', function() {
        const categoryId = $(this).data('category-delete');
        const title = $(this).data('title') || 'this category';

        confirmAction({
            title: 'Delete category?',
            message: 'Delete "' + title + '"? Assigned subjects will become uncategorized.',
            confirmText: 'Delete',
            variant: 'danger'
        }).then(function(confirmed) {
            if (!confirmed) {
                return;
            }

            requestAction('edtech_delete_category', { category_id: categoryId }, {
                loading: 'Deleting category...',
                success: 'Category deleted.',
                reload: true
            });
        });
    });

    $(document).on('click', '[data-subject-status]', function() {
        requestAction('edtech_update_subject_status', {
            subject_id: $(this).data('subject-status'),
            status: $(this).data('status')
        }, {
            loading: 'Updating subject...',
            success: 'Subject status updated.',
            reload: true
        });
    });

    $(document).on('click', '[data-category-status]', function() {
        requestAction('edtech_update_category_status', {
            category_id: $(this).data('category-status'),
            status: $(this).data('status')
        }, {
            loading: 'Updating category...',
            success: 'Category status updated.',
            reload: true
        });
    });

    $(document).on('click', '[data-user-block], [data-user-unblock]', function() {
        const isBlock = $(this).is('[data-user-block]');
        const userId = isBlock ? $(this).data('user-block') : $(this).data('user-unblock');
        const name = $(this).data('name') || 'this user';

        confirmAction({
            title: isBlock ? 'Block user?' : 'Activate user?',
            message: (isBlock ? 'Block ' : 'Activate ') + name + '?',
            confirmText: isBlock ? 'Block' : 'Activate',
            variant: isBlock ? 'danger' : 'info'
        }).then(function(confirmed) {
            if (!confirmed) {
                return;
            }

            requestAction(isBlock ? 'edtech_block_user' : 'edtech_unblock_user', { user_id: userId }, {
                loading: isBlock ? 'Blocking user...' : 'Activating user...',
                success: isBlock ? 'User blocked.' : 'User activated.',
                reload: true
            });
        });
    });

    $(document).on('click', '[data-save-settings]', function() {
        const form = document.getElementById('platformSettingsForm');
        if (!form) {
            return notify('Settings form not found.', 'error');
        }

        requestAction('edtech_save_platform_settings', formValues(form), {
            loading: 'Saving settings...',
            success: 'Settings saved.'
        });
    });

    $(document).on('click', '[data-admin-toast]', function() {
        notify($(this).data('admin-toast'), 'info');
    });

    $(document).on('input', 'input[name="title"], input[name="name"]', function() {
        const form = $(this).closest('form');
        const slugInput = form.find('input[name="slug"]');
        if (slugInput.length && !slugInput.val()) {
            slugInput.val(generateSlug($(this).val()));
        }
    });

    function initSidebar() {
        const shell = $('[data-admin-shell]');

        $('[data-sidebar-toggle]').on('click', function() {
            shell.addClass('sidebar-open');
        });

        $('[data-sidebar-close]').on('click', function() {
            shell.removeClass('sidebar-open');
        });

        $('[data-sidebar-collapse]').on('click', function() {
            shell.toggleClass('sidebar-collapsed');
            localStorage.setItem('edtechAdminSidebar', shell.hasClass('sidebar-collapsed') ? 'collapsed' : 'expanded');
        });

        if (localStorage.getItem('edtechAdminSidebar') === 'collapsed') {
            shell.addClass('sidebar-collapsed');
        }
    }

    function initTheme() {
        const shell = $('[data-admin-shell]');
        const stored = localStorage.getItem('edtechAdminTheme') || 'dark';

        setTheme(stored);

        $('[data-admin-theme-toggle]').on('click', function() {
            setTheme(shell.hasClass('is-light') ? 'dark' : 'light');
        });

        function setTheme(theme) {
            shell.toggleClass('is-light', theme === 'light');
            document.body.classList.toggle('edtech-admin-light', theme === 'light');
            document.documentElement.dataset.theme = theme;
            localStorage.setItem('edtechAdminTheme', theme);
        }
    }

    function initTables() {
        $('.edtech-data-table').each(function() {
            const table = $(this);
            const card = table.closest('.admin-table-card');
            const pageSize = parseInt(table.data('page-size'), 10) || 10;
            const pagination = card.find('[data-table-pagination]');
            let currentPage = 1;

            function rows() {
                return table.find('tbody tr').filter(function() {
                    return !$(this).find('.empty-state').length;
                });
            }

            function apply() {
                const query = (card.find('[data-table-search]').val() || '').toLowerCase();
                const visibleRows = rows().filter(function() {
                    const text = ($(this).data('search') || $(this).text()).toString().toLowerCase();
                    const match = !query || text.indexOf(query) !== -1;
                    $(this).toggle(match);
                    return match;
                });
                const totalPages = Math.max(1, Math.ceil(visibleRows.length / pageSize));
                currentPage = Math.min(currentPage, totalPages);
                visibleRows.hide().slice((currentPage - 1) * pageSize, currentPage * pageSize).show();
                renderPagination(totalPages);
            }

            function renderPagination(totalPages) {
                pagination.empty();
                if (totalPages <= 1) {
                    return;
                }
                for (let i = 1; i <= totalPages; i++) {
                    const button = $('<button type="button" class="icon-btn"></button>').text(i).toggleClass('is-active', i === currentPage);
                    button.on('click', function() {
                        currentPage = i;
                        apply();
                    });
                    pagination.append(button);
                }
            }

            card.find('[data-table-search]').on('input', function() {
                currentPage = 1;
                apply();
            });

            table.find('thead th[data-sort]').on('click', function() {
                const index = $(this).index();
                const direction = $(this).data('direction') === 'asc' ? 'desc' : 'asc';
                $(this).data('direction', direction);
                const sorted = rows().get().sort(function(a, b) {
                    const av = $(a).children().eq(index).text().trim();
                    const bv = $(b).children().eq(index).text().trim();
                    return direction === 'asc' ? av.localeCompare(bv) : bv.localeCompare(av);
                });
                table.find('tbody').append(sorted);
                apply();
            });

            apply();
        });

        $('[data-admin-global-search]').on('input', function() {
            const value = $(this).val();
            $('[data-table-search]').val(value).trigger('input');
        });
    }

    function initCharts() {
        if (!window.Chart) {
            return;
        }

        $('canvas[data-chart]').each(function() {
            const canvas = this;
            const payload = parseJson($(canvas).attr('data-chart'));
            if (!payload || $(canvas).data('chart-ready')) {
                return;
            }
            $(canvas).data('chart-ready', true);

            if (canvas.id.toLowerCase().indexOf('growth') !== -1) {
                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: payload.labels || [],
                        datasets: [
                            chartDataset('Students', payload.students || [], '#36d4ff'),
                            chartDataset('Teachers', payload.teachers || [], '#38e6a1')
                        ]
                    },
                    options: chartOptions()
                });
                return;
            }

            if (canvas.id.toLowerCase().indexOf('donut') !== -1) {
                new Chart(canvas, {
                    type: 'doughnut',
                    data: {
                        labels: payload.labels || [],
                        datasets: [{
                            data: payload.values || [],
                            backgroundColor: ['#36d4ff', '#38e6a1', '#ffd166', '#ff5c8a', '#8b7cf6'],
                            borderWidth: 0
                        }]
                    },
                    options: chartOptions(true)
                });
                return;
            }

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: payload.labels || [],
                    datasets: [{
                        label: 'Volume',
                        data: payload.values || [],
                        backgroundColor: 'rgba(54, 212, 255, 0.48)',
                        borderRadius: 8
                    }]
                },
                options: chartOptions()
            });
        });
    }

    function chartDataset(label, data, color) {
        return {
            label: label,
            data: data,
            borderColor: color,
            backgroundColor: color + '33',
            tension: 0.36,
            fill: true,
            pointRadius: 0,
            borderWidth: 2
        };
    }

    function chartOptions(donut) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: { color: '#cbd5e1', boxWidth: 10, usePointStyle: true }
                }
            },
            scales: donut ? {} : {
                x: { ticks: { color: '#91a1ba' }, grid: { color: 'rgba(255,255,255,0.06)' } },
                y: { ticks: { color: '#91a1ba' }, grid: { color: 'rgba(255,255,255,0.06)' }, beginAtZero: true }
            },
            cutout: donut ? '68%' : undefined
        };
    }

    function initCounters() {
        $('[data-counter]').each(function() {
            const el = this;
            const target = parseFloat($(el).data('counter'));
            if (Number.isNaN(target)) {
                return;
            }
            let current = 0;
            const steps = 24;
            const increment = target / steps;
            const timer = window.setInterval(function() {
                current += increment;
                if (current >= target) {
                    current = target;
                    window.clearInterval(timer);
                }
                el.textContent = Number.isInteger(target) ? Math.round(current) : current.toFixed(1);
            }, 24);
        });
    }

    function initTooltips() {
        if (!window.bootstrap || !bootstrap.Tooltip) {
            return;
        }
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
            bootstrap.Tooltip.getOrCreateInstance(el);
        });
    }

    function parseJson(raw) {
        try {
            return JSON.parse(raw || '{}');
        } catch (error) {
            return {};
        }
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function(match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[match];
        });
    }

    function escapeAttr(value) {
        return escapeHtml(value);
    }

    function addViewToUrl(view) {
        const url = new URL(window.location.href);
        url.searchParams.set('view', view);
        return url.toString();
    }

    $(document).on('click', '[data-bs-target="#subjectModal"]', function(event) {
        if (!document.getElementById('subjectModal')) {
            event.preventDefault();
            window.location.href = addViewToUrl('subjects');
        }
    });

    $(document).on('click', '.edtech-live-toggle', function() {
        const action = $(this).data('action');
        const classId = $(this).data('class');

        if (!action || !classId) {
            return;
        }

        const isLive = action === 'edtech_mark_live';
        requestAction(action, { class_id: classId }, {
            loading: isLive ? 'Starting live class...' : 'Ending live class...',
            success: isLive ? 'Live class started.' : 'Live class ended.',
            reload: true
        });
    });

    $(document).on('submit', '.edtech-assign-student-subject-form', function(event) {
        event.preventDefault();
        const values = formValues(this);

        requestAction('edtech_student_enroll_subject', values, {
            loading: 'Enrolling in subject...',
            success: 'Enrolled successfully.',
            reload: true
        });
    });

    $(document).on('submit', '.edtech-profile-form', function(event) {
        event.preventDefault();
        const values = formValues(this);

        requestAction('edtech_update_student_profile', values, {
            loading: 'Saving profile...',
            success: 'Profile saved.'
        });
    });

    $(document).on('click', '.edtech-logout-button', function() {
        requestAction('edtech_logout', {}, {
            loading: 'Logging out...',
            success: 'Logged out successfully.',
            onSuccess: function(response) {
                if ( response && response.data && response.data.redirect ) {
                    window.location.href = response.data.redirect;
                    return;
                }
                window.location.reload();
            }
        });
    });

    // Backward-compatible window functions for inline onclick handlers
    window.saveSubject = function() {
        $('[data-save-subject]').click();
    };

    window.saveCategory = function() {
        $('[data-save-category]').click();
    };

    window.deleteSubject = function(subjectId, title) {
        const btn = $('[data-subject-delete="' + subjectId + '"]');
        if (btn.length) {
            btn.click();
        }
    };

    window.deleteCategory = function(categoryId, name) {
        const btn = $('[data-category-delete="' + categoryId + '"]');
        if (btn.length) {
            btn.click();
        }
    };

    window.blockUser = function(userId, name) {
        const btn = $('[data-user-block="' + userId + '"]');
        if (btn.length) {
            btn.click();
        }
    };

    window.unblockUser = function(userId, name) {
        const btn = $('[data-user-unblock="' + userId + '"]');
        if (btn.length) {
            btn.click();
        }
    };

    window.startLiveClass = function(classId) {
        const btn = $('[data-action="edtech_mark_live"][data-class="' + classId + '"]');
        if (btn.length) {
            btn.click();
        }
    };

    window.endLiveClass = function(classId) {
        const btn = $('[data-action="edtech_end_live"][data-class="' + classId + '"]');
        if (btn.length) {
            btn.click();
        }
    };

    window.editSubject = function(subjectId) {
        notify('Subject edit is under construction.', 'info');
    };

    window.editCategory = function(categoryId) {
        notify('Category edit is under construction.', 'info');
    };

    initSidebar();
    initTheme();
    enhanceSearchableSelects();
    initTables();
    initCharts();
    initCounters();
    initTooltips();
});
