window.attachmentPreview = function attachmentPreview() {
    return {
        open: false,
        kind: 'file',
        name: '',
        previewUrl: '',
        downloadUrl: '',

        show(file) {
            this.kind = file.kind || 'file';
            this.name = file.name || '';
            this.previewUrl = file.previewUrl || '';
            this.downloadUrl = file.downloadUrl || '';
            this.open = true;
        },

        close() {
            this.open = false;
            this.previewUrl = '';
        },
    };
};
