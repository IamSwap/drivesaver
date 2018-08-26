<template>
    <div class="container">
         <div class="row">
            <div class="col-12 mb-5">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">My Files</h3>
                        <button class="btn btn-secondary ml-auto" @click="showFileUploadModal">
                            <i class="fe fe-file-plus mr-2"></i> Upload a file
                        </button>
                    </div>
                    <div class="dimmer" :class="{ 'active py-5' : loading }">
                        <div class="loader"></div>
                        <div class="dimmer-content">
                            <div class="table-responsive" v-if="files.length">
                                <table class="table card-table table-vcenter border-bottom">
                                    <thead>
                                        <tr>
                                            <th>File</th>
                                            <th style="width:30%;">Progress</th>
                                            <th class="text-center">Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="file in files" :key="file.id">
                                            <td>{{ file.name }}</td>
                                            <td style="width:30%;">
                                                <div class="d-flex justify-content-between">
                                                    <div class="mr-6">
                                                        <strong v-if="file.status === 'failed' || file.status === 'canceled'">0%</strong>
                                                        <strong v-if="file.status === 'finished'">100%</strong>
                                                        <strong v-if="file.status === 'downloading' || file.status === 'uploading'">
                                                            <span v-if="file.progress">
                                                                {{ totalProgress(file) }}%
                                                            </span>
                                                            <span v-else>0%</span>
                                                        </strong>
                                                    </div>
                                                    <div>
                                                        <span v-if="file.status === 'downloading' || file.status === 'uploading'">
                                                            <span v-if="file.progress">
                                                                <small class="text-muted" v-if="file.status === 'downloading'">
                                                                    Downloaded {{ formatSize(file.progress.downloaded) }} of {{ formatSize(file.progress.file_size) }} ({{ formatSize(file.progress.download_rate) }}/s)
                                                                </small>
                                                                <small class="text-muted" v-if="file.status === 'uploading'">
                                                                    Uploaded {{ formatSize(file.progress.uploaded) }} of {{ formatSize(file.progress.file_size) }} ({{ formatSize(file.progress.upload_rate) }}/s)
                                                                </small>
                                                            </span>
                                                            <small class="text-muted" v-else>Waiting...</small>
                                                        </span>
                                                        <small class="text-muted" v-if="file.status === 'finished'">
                                                            Finished
                                                        </small>
                                                        <small class="text-muted" v-if="file.status === 'canceled'">
                                                            Canceled
                                                        </small>
                                                        <small class="text-muted" v-if="file.status === 'failed'">
                                                            Failed
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="progress progress-xs">
                                                    <div class="progress-bar" :class="progressClass(file)" role="progressbar" :style="{ width:`${ totalProgress(file) }%`}" :aria-valuenow="totalProgress(file)" aria-valuemin="0" aria-valuemax="100"></div>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge" :class="statusClass(file.status)">{{ file.status }}</span>
                                            </td>
                                            <td class="text-right">
                                                <div class="d-flex align-items-center justify-content-end">
                                                    <button class="btn btn-warning btn-sm" @click="cancel(file)" v-if="file.status === 'downloading' || file.status === 'uploading'">Cancel</button>
                                                    <button class="btn btn-secondary btn-sm mr-2" @click="retry(file)" v-if="file.status === 'failed' || file.status === 'canceled'">Retry</button>
                                                    <button class="btn btn-secondary btn-sm mr-2" @click="redownload(file)" v-if="file.status === 'finished'">Redownload</button>
                                                    <button class="btn btn-danger btn-sm" v-if="file.status === 'finished' || file.status === 'failed' || file.status === 'canceled'" @click="remove(file)">Remove</button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-center py-5" v-else>
                                <p>There no files yet. Please upload one.</p>
                                <button class="btn btn-secondary" @click="showFileUploadModal">
                                    <i class="fe fe-file-plus mr-2"></i> Upload a file
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="modal fade" tabindex="-1" role="dialog" ref="modalFile">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title" id="modelTitleId">Upload a file</h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form @submit.prevent="uploadFile()" ref="formUpload">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="name">Name: <span class="text-muted">(optional)</span></label>
                                <input type="text" name="name" class="form-control">
                            </div>
                            <div class="form-group">
                                <label for="name">URL: <span class="form-required">*</span></label>
                                <input type="url" name="url" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Upload</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
export default {
    props: ['user'],
    data() {
        return {
            files: [],
            pagination: {},
            loading: false,
        }
    },
    mounted() {
        this.fetch();
    },
    methods: {
        fetch() {
            this.loading = true;
            axios.get('/api/files')
                .then(response => {
                    this.files = response.data;
                    this.loading = false;
                    this.files.map((file) => {
                        if (file.status === 'downloading' || file.status === 'uploading') {
                            this.listenProgress(file);
                        }
                    });
                }, error => {
                    this.loading = false;
                });
        },
        statusClass(status) {
            if (status === 'finished') {
                return 'badge-success';
            }

            if (status === 'uploading' || status === 'downloading') {
                return 'badge-warning';
            }

            if (status === 'failed') {
                return 'badge-danger';
            }

            if (status === 'canceled') {
                return 'badge-secondary';
            }
        },
        formatSize(bytes, decimals) {
            if (bytes == 0) return '0 Bytes';

            var k = 1024,
                dm = decimals || 2,
                sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
                i = Math.floor(Math.log(bytes) / Math.log(k));

            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        },
        totalProgress(file) {
            if (file.status === 'finished') {
                return 100;
            }
            if (file.status === 'failed') {
                return 0;
            }
            return (file.progress) ? Math.round((file.progress.download_progress + file.progress.upload_progress) / 2) : 0;
        },
        progressColor(file) {
            if (file.status === 'finished') {
                return 'green';
            }

            if (file.status === 'failed') {
                return 'red';
            }

            if (this.totalProgress(file) <= 25) {
                return 'orange';
            }

            if (this.totalProgress(file) <= 50) {
                return 'yellow';
            }

            if (this.totalProgress(file) <= 75) {
                return 'lime';
            }

            if (this.totalProgress(file) <= 100) {
                return 'green';
            }
        },
        progressClass(file) {
            if (file.status === 'finished') {
                return 'bg-green';
            }

            if (file.status === 'failed') {
                return 'bg-red';
            }

            if (this.totalProgress(file) <= 25) {
                return 'bg-orange';
            }

            if (this.totalProgress(file) <= 50) {
                return 'bg-yellow';
            }

            if (this.totalProgress(file) <= 75) {
                return 'bg-lime';
            }

            if (this.totalProgress(file) <= 100) {
                return 'bg-green';
            }
        },
        listenProgress(file) {
            Echo.disconnect();

            Echo.private(`progress.${file.uuid}.${this.user.id}`)
                .listen('Progress', (e) => {
                    this.files = this.files.map(f => {
                        if (f.id === file.id) {
                            f.progress = e.progress;
                            f.status = e.progress.status;
                        }
                        return f;
                    });
                })
                .listen('DownloadFailed', (e) => {
                    this.files = this.files.map(f => {
                        if (f.id === file.id) {
                            f.progress = '';
                            f.status = e.file.status;
                        }
                        return f;
                    });
                });
        },
        showFileUploadModal() {
            $(this.$refs.modalFile).modal('show');
        },
        uploadFile() {
            Bus.$emit('showLoader');
            let formData = new FormData(this.$refs.formUpload);
            axios.post('/api/files', formData)
                .then(response => {
                    this.fetch();
                    $(this.$refs.modalFile).modal('hide');
                    this.$refs.formUpload.reset();
                    Bus.$emit('hideLoader');
                    swal('Added', 'File added successfully! It will be uploaded shortly to Google Drive.', 'success');
                }, error => {
                    Bus.$emit('hideLoader');
                    swal('Oops!', 'Something went wrong!', 'error');
                });
        },
        cancel(file) {
            swal({
                title: "Are you sure?",
                text: "File will not be uploaded to Google Drive!",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((caneled) => {
                if (caneled) {
                    Bus.$emit('showLoader');
                    axios.post(`/api/files/${file.id}/cancel`)
                        .then(response => {
                            this.fetch();
                            Bus.$emit('hideLoader');
                            swal('Cancel!', 'File upload canelled!', 'success');
                        }, error => {
                            Bus.$emit('hideLoader');
                            swal('Oops!', error.response.data.message, 'error');
                        });
                }
            });
        },
        remove(file) {
            swal({
                title: "Are you sure?",
                text: "Want to delete this file?",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((removed) => {
                if (removed) {
                    Bus.$emit('showLoader');
                    axios.delete(`/api/files/${file.id}`)
                        .then(response => {
                            this.fetch();
                            Bus.$emit('hideLoader');
                            swal('Removed!', 'File removed successfully!', 'success');
                        }, error => {
                            Bus.$emit('hideLoader');
                            swal('Oops!', error.response.data.message, 'error');
                        });
                }
            });
        },
        retry(file) {
            swal({
                title: "Are you sure?",
                text: "Want to retry downloading this file?",
                icon: "warning",
                buttons: ['No', 'Yes!'],
                dangerMode: false,
            }).then((retry) => {
                if (retry) {
                    Bus.$emit('showLoader');
                    axios.post(`/api/files/${file.id}/retry`)
                        .then(response => {
                            this.fetch();
                            Bus.$emit('hideLoader');
                            swal('Okay!', 'File will be downloaded shortly!', 'success');
                        }, error => {
                            Bus.$emit('hideLoader');
                            swal('Oops!', error.response.data.message, 'error');
                        });
                }
            });
        },
        redownload(file) {
            swal({
                title: "Are you sure?",
                text: "Want to redownload this file?",
                icon: "warning",
                buttons: ['No', 'Yes!'],
                dangerMode: false,
            }).then((redownload) => {
                if (redownload) {
                    Bus.$emit('showLoader');
                    axios.post(`/api/files/${file.id}/redownload`)
                        .then(response => {
                            this.fetch();
                            Bus.$emit('hideLoader');
                            swal('Okay!', 'File will be redownloaded shortly!', 'success');
                        }, error => {
                            Bus.$emit('hideLoader');
                            swal('Oops!', error.response.data.message, 'error');
                        });
                }
            });
        },
    }
}
</script>

<style>
</style>
