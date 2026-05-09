<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Edtech_Recorded_Classes {
    private $db;
    private $helpers;

    public function __construct( $db, $helpers ) {
        $this->db = $db;
        $this->helpers = $helpers;
    }

    public function render_teacher_recorded_classes() {
        if ( ! is_user_logged_in() || ! $this->helpers->is_teacher() ) {
            return '<div class="glass-card p-5 text-center"><h3>Please login as a teacher to manage recorded classes.</h3></div>';
        }

        $teacher_id = get_current_user_id();
        $subjects = $this->db->get_teacher_subjects( $teacher_id );
        $videos = $this->db->get_recorded_videos_by_teacher( $teacher_id );

        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <h1 class="section-title mb-2">Recorded Classes</h1>
            <p class="text-muted">Upload and manage your YouTube-based recorded lessons in a modern library experience.</p>
        </div>
        <div class="glass-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="text-white mb-1">Add / Edit Recorded Class</h5>
                    <p class="text-muted small mb-0">Use YouTube links and thumbnail previews — no large video uploads.</p>
                </div>
                <button class="btn btn-brand btn-sm" data-bs-toggle="collapse" data-bs-target="#recordedClassForm" aria-expanded="false">
                    <i class="fa-solid fa-plus me-2"></i>Add Recording
                </button>
            </div>
            <div class="collapse" id="recordedClassForm">
                <div class="card bg-light border p-4 mb-4">
                    <form class="edtech-recorded-class-form" enctype="multipart/form-data">
                        <div class="row g-3">
                            <input type="hidden" name="video_id" value="0">
                            <div class="col-md-6">
                                <label class="form-label">Video Title</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subject</label>
                                <select class="form-select" name="subject_id" required>
                                    <option value="">Choose a subject</option>
                                    <?php foreach ( $subjects as $subject ) : ?>
                                        <option value="<?php echo esc_attr( $subject->id ); ?>"><?php echo esc_html( $subject->title ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">YouTube Video URL</label>
                                <input type="url" class="form-control" name="youtube_url" placeholder="https://youtu.be/... or https://youtube.com/watch?v=..." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Duration</label>
                                <input type="text" class="form-control" name="duration" placeholder="e.g. 35 min" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Video Description</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tags</label>
                                <input type="text" class="form-control" name="tags" placeholder="math, algebra, test prep">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Visibility Status</label>
                                <select class="form-select" name="visibility">
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Thumbnail Image</label>
                                <input type="file" class="form-control" name="thumbnail" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notes / Resources (PDF)</label>
                                <input type="file" class="form-control" name="notes_file" accept="application/pdf">
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="submit" class="btn btn-brand">Publish</button>
                                    <button type="button" class="btn btn-outline-secondary edtech-save-draft">Save Draft</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="edtech-recorded-video-preview d-none mb-4">
                <h6 class="text-white mb-2">Preview</h6>
                <div class="ratio ratio-16x9 recorded-class-embed"></div>
            </div>
        </div>
        <div class="glass-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="text-white mb-0">My Recorded Classes</h5>
                <span class="badge bg-secondary"><?php echo esc_html( count( $videos ) ); ?> videos</span>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless align-middle text-white mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Duration</th>
                            <th>Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $videos ) ) : ?>
                            <?php foreach ( $videos as $video ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $video->title ); ?></td>
                                    <td><?php echo esc_html( $video->subject_title ); ?></td>
                                    <td><span class="badge bg-<?php echo 'published' === $video->visibility ? 'success' : 'secondary'; ?>"><?php echo esc_html( ucfirst( $video->visibility ) ); ?></span></td>
                                    <td><?php echo esc_html( $video->duration ); ?></td>
                                    <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $video->created_at ) ) ); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-light edtech-edit-recorded-video" data-video='<?php echo wp_json_encode( $video ); ?>'>Edit</button>
                                        <button class="btn btn-sm btn-danger edtech-delete-recorded-video" data-video-id="<?php echo esc_attr( $video->id ); ?>">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6" class="text-muted text-center py-4">No recorded classes yet. Add your first YouTube lesson above.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_video_library() {
        if ( ! is_user_logged_in() || ! $this->helpers->is_student() ) {
            return '<div class="glass-card p-5 text-center"><h3>Please login as a student to access the video library.</h3></div>';
        }

        $student_id = get_current_user_id();
        $subjects = $this->db->get_student_subjects( $student_id );
        $teachers = $this->db->get_teachers_for_subject_assignment();
        $videos = $this->db->get_recorded_videos_for_student( $student_id );
        $recent_history = $this->db->get_recent_watch_history( $student_id, 6 );

        ob_start();
        ?>
        <div class="edtech-video-library-page">
            <div class="edtech-dashboard-header mb-5">
                <h1 class="section-title mb-2">Recorded Classes Library</h1>
                <p class="text-muted">Browse, search, and filter recorded lessons by subject, teacher, tags, and recent uploads.</p>
            </div>
        <div class="row gy-4 mb-4">
            <div class="col-lg-8">
                <div class="glass-card p-4">
                    <form class="row g-3 edtech-video-search-form">
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="search" placeholder="Search by title, subject, teacher or tags">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="subject_id">
                                <option value="">All subjects</option>
                                <?php foreach ( $subjects as $subject ) : ?>
                                    <option value="<?php echo esc_attr( $subject->id ); ?>"><?php echo esc_html( $subject->title ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="teacher_id">
                                <option value="">All teachers</option>
                                <?php foreach ( $teachers as $teacher ) : ?>
                                    <option value="<?php echo esc_attr( $teacher->user_id ); ?>"><?php echo esc_html( $teacher->full_name ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-brand">Search Videos</button>
                            <button type="button" class="btn btn-outline-light edtech-reset-video-search">Reset</button>
                        </div>
                    </form>
                </div>
                <div class="row row-cols-1 row-cols-md-2 g-4 mt-3 edtech-video-grid">
                    <?php if ( ! empty( $videos ) ) : ?>
                        <?php foreach ( $videos as $video ) : ?>
                            <div class="col">
                                <?php echo $this->render_video_card( $video ); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="col-12">
                            <div class="glass-card p-4 text-center">
                                <h5 class="text-white">No videos available yet.</h5>
                                <p class="text-muted">Your teacher will add recorded lessons once they are uploaded to YouTube.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="glass-card p-4 mb-4">
                    <h5 class="text-white mb-3">Continue Learning</h5>
                    <?php if ( ! empty( $recent_history ) ) : ?>
                        <?php foreach ( $recent_history as $history ) : $video = $this->db->get_recorded_video_by_id( $history->video_id ); if ( ! $video ) { continue; } ?>
                            <a href="<?php echo esc_url( site_url( '/video-player/' . absint( $video->id ) ) ); ?>" class="d-block text-decoration-none mb-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="flex-shrink-0" style="width:72px;">
                                        <img src="<?php echo esc_url( $this->get_thumbnail_url( $video ) ); ?>" alt="<?php echo esc_attr( $video->title ); ?>" class="img-fluid rounded" />
                                    </div>
                                    <div>
                                        <div class="text-white fw-semibold"><?php echo esc_html( $video->title ); ?></div>
                                        <small class="text-muted"><?php echo esc_html( $video->duration ); ?> • <?php echo esc_html( $video->subject_title ); ?></small>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="text-muted mb-0">Start a lesson and your recently watched classes will appear here.</p>
                    <?php endif; ?>
                </div>
                <div class="glass-card p-4">
                    <h5 class="text-white mb-3">Filter Tips</h5>
                    <p class="text-muted small mb-2">- Use subject filters to surface enrolled videos.</p>
                    <p class="text-muted small mb-2">- Search tags or teacher names for quick matching.</p>
                    <p class="text-muted small mb-0">- Click "Watch" to open the embedded player and save progress automatically.</p>
                </div>
            </div>
        </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_video_player() {
        if ( ! is_user_logged_in() ) {
            return '<div class="glass-card p-5 text-center"><h3>Please login to watch recorded classes.</h3></div>';
        }

        $video_id = absint( get_query_var( 'edtech_video_id', 0 ) ?: $_GET['edtech_video_id'] ?? 0 );
        if ( ! $video_id ) {
            return '<div class="glass-card p-5 text-center"><h3>Video not found.</h3></div>';
        }

        $video = $this->db->get_recorded_video_by_id( $video_id );
        if ( ! $video || 'published' !== $video->visibility ) {
            return '<div class="glass-card p-5 text-center"><h3>Video unavailable.</h3></div>';
        }

        $student_id = get_current_user_id();
        if ( $this->helpers->is_student() ) {
            $this->db->record_video_history( $student_id, $video_id, 0 );
        }

        $related_videos = $this->db->get_related_recorded_videos( $video->id, $video->subject_id );
        $history = $this->db->get_video_history_item( get_current_user_id(), $video_id );

        ob_start();
        ?>
        <div class="edtech-video-player-page" data-video-id="<?php echo esc_attr( $video->id ); ?>">
            <div class="row gy-4">
                <div class="col-lg-8">
                    <div class="glass-card p-4 mb-4">
                        <div class="ratio ratio-16x9 mb-4">
                            <iframe src="<?php echo esc_url( $this->get_youtube_embed_url( $video->youtube_url ) ); ?>" title="<?php echo esc_attr( $video->title ); ?>" allowfullscreen allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
                        </div>
                        <h2 class="text-white mb-2"><?php echo esc_html( $video->title ); ?></h2>
                        <p class="text-muted mb-3"><?php echo esc_html( $video->description ); ?></p>
                        <div class="row row-cols-1 row-cols-md-3 g-3 mb-4">
                            <div class="col"><span class="text-muted">Teacher</span><div class="text-white"><?php echo esc_html( $video->teacher_name ); ?></div></div>
                            <div class="col"><span class="text-muted">Subject</span><div class="text-white"><?php echo esc_html( $video->subject_title ); ?></div></div>
                            <div class="col"><span class="text-muted">Duration</span><div class="text-white"><?php echo esc_html( $video->duration ); ?></div></div>
                        </div>
                        <div class="d-flex flex-wrap gap-3 mb-3">
                            <span class="badge bg-info">Uploaded <?php echo esc_html( human_time_diff( strtotime( $video->created_at ), current_time( 'timestamp' ) ) ); ?> ago</span>
                            <?php if ( $history ) : ?>
                                <span class="badge bg-success">Progress: <?php echo esc_html( intval( $history->progress ) ); ?>%</span>
                            <?php endif; ?>
                        </div>
                        <?php if ( $video->notes_file ) : ?>
                            <a href="<?php echo esc_url( $video->notes_file ); ?>" class="btn btn-outline-light btn-sm" target="_blank"><i class="fa-solid fa-file-pdf me-2"></i>Download Notes</a>
                        <?php endif; ?>
                    </div>
                    <div class="glass-card p-4">
                        <h5 class="text-white mb-3">About this class</h5>
                        <p class="text-muted mb-0"><?php echo esc_html( $video->description ); ?></p>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="glass-card p-4 mb-4">
                        <h5 class="text-white mb-3">Related Lessons</h5>
                        <?php if ( ! empty( $related_videos ) ) : ?>
                            <?php foreach ( $related_videos as $related ) : ?>
                                <a href="<?php echo esc_url( site_url( '/video-player/' . absint( $related->id ) ) ); ?>" class="d-block text-decoration-none mb-3">
                                    <div class="d-flex gap-3 align-items-center">
                                        <img src="<?php echo esc_url( $this->get_thumbnail_url( $related ) ); ?>" width="84" height="56" class="rounded object-fit-cover" alt="<?php echo esc_attr( $related->title ); ?>">
                                        <div>
                                            <div class="text-white small fw-semibold"><?php echo esc_html( $related->title ); ?></div>
                                            <small class="text-muted"><?php echo esc_html( $related->duration ); ?></small>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <p class="text-muted mb-0">No related videos yet. Check back as more classes are added.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_video_card( $video ) {
        $thumbnail = $this->get_thumbnail_url( $video );
        $watch_url = site_url( '/video-player/' . absint( $video->id ) );
        ob_start();
        ?>
        <div class="glass-card overflow-hidden h-100 video-card">
            <div class="position-relative overflow-hidden">
                <img src="<?php echo esc_url( $thumbnail ); ?>" class="img-fluid w-100" alt="<?php echo esc_attr( $video->title ); ?>">
                <div class="video-card-hover position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0, 0, 0, 0.28); opacity: 0; transition: opacity .2s ease;">
                    <i class="fa-solid fa-play-circle fa-3x text-white"></i>
                </div>
            </div>
            <div class="p-4">
                <h5 class="text-white mb-2"><?php echo esc_html( $video->title ); ?></h5>
                <div class="text-muted small mb-3"><?php echo esc_html( $video->teacher_name ); ?> • <?php echo esc_html( $video->subject_title ); ?></div>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted"><?php echo esc_html( $video->duration ); ?></small>
                    <a href="<?php echo esc_url( $watch_url ); ?>" class="btn btn-sm btn-brand">Watch</a>
                </div>
            </div>
        </div>
        <style>
            .video-card:hover .video-card-hover { opacity: 1; }
            .object-fit-cover { object-fit: cover; }
        </style>
        <?php
        return ob_get_clean();
    }

    private function get_youtube_embed_url( $url ) {
        $video_id = $this->extract_youtube_id( $url );
        if ( ! $video_id ) {
            return esc_url( $url );
        }
        return 'https://www.youtube.com/embed/' . $video_id . '?rel=0&modestbranding=1';
    }

    private function extract_youtube_id( $url ) {
        if ( empty( $url ) ) {
            return false;
        }
        $matches = array();
        if ( preg_match( '#(?:youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|v/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $matches ) ) {
            return $matches[1];
        }
        return false;
    }

    private function get_thumbnail_url( $video ) {
        if ( ! empty( $video->thumbnail ) ) {
            return esc_url_raw( $video->thumbnail );
        }
        $youtube_id = $this->extract_youtube_id( $video->youtube_url );
        if ( $youtube_id ) {
            return 'https://i.ytimg.com/vi/' . esc_attr( $youtube_id ) . '/hqdefault.jpg';
        }
        return esc_url_raw( includes_url( 'images/media/video.png' ) );
    }
}
