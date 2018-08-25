<template>
    <div>
        <!-- <p v-if="downloadProgress.total">Total: {{ format(downloadProgress.total) }}</p>
        <p v-if="downloadProgress.downloaded">Downloaded: {{ format(downloadProgress.downloaded) }}</p>
        <p v-if="uploadProgress.total">Total Upload: {{ format(uploadProgress.total) }}</p>
        <p v-if="uploadProgress.uploaded">Uploaded: {{ format(uploadProgress.uploaded) }}</p> -->
    </div>
</template>

<script>
export default {
    props: ['user'],
    data() {
        return {
            progress: {},
        }
    },
    mounted() {
        Echo.private(`progress.${this.user.id}`)
            .listen('Progress', (e) => {
                //this.progress = e.data;
                console.log(e.data);
            });
    },
    methods: {
        format(bytes, decimals) {
            if (bytes == 0) return '0 Bytes';

            var k = 1024,
                dm = decimals || 2,
                sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'],
                i = Math.floor(Math.log(bytes) / Math.log(k));

            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }
    }
}
</script>

