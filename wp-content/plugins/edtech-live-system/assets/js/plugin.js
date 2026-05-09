jQuery(function($) {
    'use strict';

    var UI = window.EDTECH_UI || {};

    function showSuccess(message, options) {
        return UI.showSuccess ? UI.showSuccess(message, options) : console.log(message);
    }

    function showError(message, options) {
        return UI.showError ? UI.showError(message, options) : console.error(message);
    }

    function showInfo(message, options) {
        return UI.showInfo ? UI.showInfo(message, options) : console.info(message);
    }

    function setButtonLoading(button, loading, label) {
        if (UI.setButtonLoading) {
            UI.setButtonLoading(button, loading, label);
            return;
        }
        $(button).prop('disabled', loading);
    }

    function confirmAction(options) {
        if (UI.showConfirmModal) {
            return UI.showConfirmModal(options);
        }
        return Promise.resolve(true);
    }

    function requestAjax(config) {
        var submitButton = config.button ? $(config.button)[0] : null;
        var loadingLabel = config.loadingLabel || 'Working...';

        if (submitButton) {
            setButtonLoading(submitButton, true, loadingLabel);
        }

        return $.ajax(config.ajax).done(function(response) {
            if (response && response.success) {
                if (config.successMessage !== false) {
                    showSuccess(response.data && response.data.message ? response.data.message : (config.successMessage || 'Done'));
                }
                if (typeof config.onSuccess === 'function') {
                    config.onSuccess(response);
                }
                return;
            }

            showError(response && response.data && response.data.message ? response.data.message : (config.errorMessage || 'Something went wrong.'));
            if (typeof config.onError === 'function') {
                config.onError(response);
            }
        }).fail(function() {
            showError(config.failMessage || 'Request failed. Please try again.');
        }).always(function() {
            if (submitButton) {
                setButtonLoading(submitButton, false);
            }
        });
    }

    function submitAjaxForm(form, options) {
        options = options || {};
        var formData = new FormData(form[0]);
        var submitButton = form.find('button[type="submit"]').first()[0];

        requestAjax({
            button: submitButton,
            loadingLabel: options.loadingLabel || 'Submitting...',
            ajax: {
                url: EDTECH_AJAX.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            },
            onSuccess: function(response) {
                if (options.reset) {
                    form[0].reset();
                }
                if (options.collapse) {
                    $(options.collapse).collapse('hide');
                }
                if (response.data && response.data.redirect) {
                    window.setTimeout(function() {
                        window.location.href = response.data.redirect;
                    }, 650);
                } else if (options.reload) {
                    window.setTimeout(function() {
                        location.reload();
                    }, 650);
                }
            },
            onError: options.onError
        });
    }

    $('#edtech-login-form, #edtech-student-register-form, #edtech-teacher-register-form, #edtech-forgot-password-form, #edtech-reset-password-form').on('submit', function(e) {
        e.preventDefault();
        submitAjaxForm($(this), { loadingLabel: 'Checking...' });
    });

    $(document).on('click', '.edtech-logout-button', function(e) {
        e.preventDefault();
        confirmAction({
            title: 'Log out?',
            message: 'Your current session will end securely.',
            confirmText: 'Logout',
            cancelText: 'Stay signed in'
        }).then(function(confirmed) {
            if (!confirmed) {
                return;
            }

            requestAjax({
                button: e.currentTarget,
                loadingLabel: 'Logging out...',
                ajax: {
                    url: EDTECH_AJAX.ajax_url,
                    type: 'POST',
                    data: { action: 'edtech_logout', nonce: EDTECH_AJAX.nonce }
                },
                onSuccess: function(response) {
                    window.location.href = response.data.redirect || EDTECH_AJAX.home_url;
                }
            });
        });
    });

    $(document).on('click', '.edtech-refresh-routes-btn, .edtech-reinit-platform-btn', function(e) {
        e.preventDefault();
        var action = $(this).data('action');
        requestAjax({
            button: this,
            loadingLabel: action === 'edtech_reinitialize_platform' ? 'Reinitializing...' : 'Refreshing...',
            ajax: {
                url: EDTECH_AJAX.ajax_url,
                type: 'POST',
                data: { action: action, nonce: EDTECH_AJAX.nonce }
            }
        });
    });

    $(document).on('click', '.edtech-live-toggle', function(e) {
        e.preventDefault();
        var button = this;
        var action = $(button).data('action');
        var classId = $(button).data('class');
        var isEnding = action === 'edtech_end_live';

        if (!classId) {
            return;
        }

        confirmAction({
            title: isEnding ? 'End live class?' : 'Start live class?',
            message: isEnding ? 'Students will no longer be able to join this session.' : 'Students will see this class as live.',
            confirmText: isEnding ? 'End class' : 'Go live',
            variant: isEnding ? 'danger' : 'info'
        }).then(function(confirmed) {
            if (!confirmed) {
                return;
            }

            requestAjax({
                button: button,
                loadingLabel: isEnding ? 'Ending...' : 'Starting...',
                ajax: {
                    url: EDTECH_AJAX.ajax_url,
                    type: 'POST',
                    data: { action: action, class_id: classId, nonce: EDTECH_AJAX.nonce }
                },
                onSuccess: function() {
                    window.setTimeout(function() {
                        location.reload();
                    }, 650);
                }
            });
        });
    });

    $(document).on('click', '.edtech-approve-student, .edtech-approve-teacher', function(e) {
        e.preventDefault();
        var button = this;
        var isTeacher = $(button).hasClass('edtech-approve-teacher');
        var userId = $(button).data('user-id');
        var userName = $(button).data('name') || (isTeacher ? 'this teacher' : 'this student');

        confirmAction({
            title: isTeacher ? 'Approve teacher?' : 'Approve student?',
            message: 'Approve ' + userName + ' for platform access.',
            confirmText: 'Approve',
            cancelText: 'Cancel'
        }).then(function(confirmed) {
            if (!confirmed) {
                return;
            }

            requestAjax({
                button: button,
                loadingLabel: 'Approving...',
                ajax: {
                    url: EDTECH_AJAX.ajax_url,
                    type: 'POST',
                    data: {
                        action: isTeacher ? 'edtech_approve_teacher' : 'edtech_approve_student',
                        user_id: userId,
                        nonce: EDTECH_AJAX.nonce
                    }
                },
                onSuccess: function() {
                    window.setTimeout(function() {
                        location.reload();
                    }, 650);
                }
            });
        });
    });

    $(document).on('submit', '.edtech-assign-student-form, .edtech-assign-student-subject-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        formData.append('action', 'edtech_assign_subject_to_student');
        formData.append('nonce', EDTECH_AJAX.nonce);

        requestAjax({
            button: form.find('button[type="submit"]').first()[0],
            loadingLabel: 'Assigning...',
            ajax: {
                url: EDTECH_AJAX.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            },
            onSuccess: function() {
                form[0].reset();
                $('#assignStudentSubjectForm').collapse('hide');
                window.setTimeout(function() {
                    location.reload();
                }, 650);
            }
        });
    });

    $(document).on('submit', '.edtech-assign-teacher-form, .edtech-assign-teacher-subject-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        formData.append('action', 'edtech_assign_subject_to_teacher');
        formData.append('nonce', EDTECH_AJAX.nonce);

        requestAjax({
            button: form.find('button[type="submit"]').first()[0],
            loadingLabel: 'Assigning...',
            ajax: {
                url: EDTECH_AJAX.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            },
            onSuccess: function() {
                form[0].reset();
                $('#assignSubjectForm').collapse('hide');
                window.setTimeout(function() {
                    location.reload();
                }, 650);
            }
        });
    });

    $(document).on('submit', '.edtech-create-live-class-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        formData.append('action', 'edtech_create_live_class');
        formData.append('nonce', EDTECH_AJAX.nonce);

        requestAjax({
            button: form.find('button[type="submit"]').first()[0],
            loadingLabel: 'Creating...',
            ajax: {
                url: EDTECH_AJAX.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            },
            onSuccess: function() {
                form[0].reset();
                $('#createClassForm').collapse('hide');
                window.setTimeout(function() {
                    location.reload();
                }, 650);
            }
        });
    });

    function renderVideoCards(videos) {
        var grid = $('.edtech-video-grid');
        grid.empty();

        if (!videos || !videos.length) {
            grid.append('<div class="col-12"><div class="glass-card p-4 text-center"><h5>No videos found.</h5><p class="text-muted mb-0">Try a different filter or search term.</p></div></div>');
            return;
        }

        videos.forEach(function(video) {
            var thumbnail = video.thumbnail || 'https://i.ytimg.com/vi/' + extractYoutubeId(video.youtube_url) + '/hqdefault.jpg';
            var watchUrl = EDTECH_AJAX.home_url.replace(/\/$/, '') + '/video-player/' + video.id;
            var card =
                '<div class="col">' +
                    '<div class="glass-card overflow-hidden h-100 video-card">' +
                        '<div class="position-relative overflow-hidden edtech-video-thumb">' +
                            '<img src="' + escapeHtml(thumbnail) + '" class="img-fluid w-100" alt="' + escapeHtml(video.title) + '">' +
                            '<div class="video-card-hover position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">' +
                                '<i class="fa-solid fa-play-circle fa-3x text-white"></i>' +
                            '</div>' +
                        '</div>' +
                        '<div class="p-4">' +
                            '<h5 class="mb-2">' + escapeHtml(video.title) + '</h5>' +
                            '<div class="text-muted small mb-3">' + escapeHtml(video.teacher_name || '') + ' · ' + escapeHtml(video.subject_title || '') + '</div>' +
                            '<div class="d-flex justify-content-between align-items-center gap-3">' +
                                '<small class="text-muted">' + escapeHtml(video.duration || '') + '</small>' +
                                '<a href="' + watchUrl + '" class="btn btn-sm btn-brand">Watch</a>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                '</div>';
            grid.append(card);
        });
    }

    function extractYoutubeId(url) {
        var match = String(url || '').match(/(?:youtube(?:-nocookie)?\.com\/(?:watch\?v=|embed\/|v\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/);
        return match ? match[1] : '';
    }

    function escapeHtml(text) {
        return $('<div/>').text(text || '').html();
    }

    $(document).on('submit', '.edtech-video-search-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        var submitButton = form.find('button[type="submit"]').first()[0];
        formData.append('action', 'edtech_search_videos');
        formData.append('nonce', EDTECH_AJAX.nonce);

        requestAjax({
            button: submitButton,
            loadingLabel: 'Searching...',
            successMessage: false,
            ajax: {
                url: EDTECH_AJAX.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            },
            onSuccess: function(response) {
                renderVideoCards(response.data.videos);
            },
            failMessage: 'Search request failed.'
        });
    });

    $(document).on('click', '.edtech-reset-video-search', function(e) {
        e.preventDefault();
        var form = $('.edtech-video-search-form');
        if (form.length) {
            form[0].reset();
            form.trigger('submit');
        }
    });

    $(document).on('submit', '.edtech-recorded-class-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        formData.append('action', 'edtech_save_recorded_class');
        formData.append('nonce', EDTECH_AJAX.nonce);

        requestAjax({
            button: form.find('button[type="submit"]').first()[0],
            loadingLabel: 'Saving...',
            ajax: {
                url: EDTECH_AJAX.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            },
            onSuccess: function() {
                window.setTimeout(function() {
                    location.reload();
                }, 650);
            }
        });
    });

    $(document).on('click', '.edtech-delete-recorded-video', function(e) {
        e.preventDefault();
        var button = this;
        var videoId = $(button).data('video-id');

        if (!videoId) {
            return;
        }

        confirmAction({
            title: 'Delete this lesson?',
            message: 'This recorded class will be removed from the library.',
            confirmText: 'Delete',
            cancelText: 'Cancel',
            variant: 'danger'
        }).then(function(confirmed) {
            if (!confirmed) {
                return;
            }

            requestAjax({
                button: button,
                loadingLabel: 'Deleting...',
                ajax: {
                    url: EDTECH_AJAX.ajax_url,
                    type: 'POST',
                    data: { action: 'edtech_delete_recorded_class', video_id: videoId, nonce: EDTECH_AJAX.nonce }
                },
                onSuccess: function() {
                    window.setTimeout(function() {
                        location.reload();
                    }, 650);
                }
            });
        });
    });

    $(document).on('click', '.edtech-edit-recorded-video', function(e) {
        e.preventDefault();
        var video = $(this).data('video');

        if (!video) {
            return;
        }

        var form = $('.edtech-recorded-class-form');
        form.find('[name="video_id"]').val(video.id);
        form.find('[name="title"]').val(video.title);
        form.find('[name="subject_id"]').val(video.subject_id);
        form.find('[name="youtube_url"]').val(video.youtube_url);
        form.find('[name="duration"]').val(video.duration);
        form.find('[name="description"]').val(video.description);
        form.find('[name="tags"]').val(video.tags);
        form.find('[name="visibility"]').val(video.visibility);
        $('#recordedClassForm').collapse('show');

        var preview = $('.edtech-recorded-video-preview');
        preview.find('.recorded-class-embed').html('<iframe class="w-100 h-100" src="https://www.youtube.com/embed/' + extractYoutubeId(video.youtube_url) + '?rel=0&modestbranding=1" allowfullscreen></iframe>');
        preview.removeClass('d-none');
        showInfo('Recording loaded for editing.');
    });

    if ($('.edtech-video-library-page').length) {
        $('.edtech-video-search-form').trigger('submit');
    }

    if ($('.edtech-video-player-page').length) {
        var videoId = $('.edtech-video-player-page').data('video-id');
        if (videoId) {
            $.post(EDTECH_AJAX.ajax_url, {
                action: 'edtech_save_watch_history',
                video_id: videoId,
                progress: 0,
                nonce: EDTECH_AJAX.nonce
            });
        }
    }
});
