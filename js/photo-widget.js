class PhotoWidget {
    constructor() {
        this.video = null;
        this.canvas = null;
        this.context = null;
        this.stream = null;
    }

    init() {
        this.video = document.createElement('video');
        this.canvas = document.createElement('canvas');
        this.context = this.canvas.getContext('2d');
        this.renderUI();
        this.attachEventListeners();
    }

    renderUI() {
        document.body.appendChild(this.video);
        document.body.appendChild(this.canvas);
        const captureButton = document.createElement('button');
        captureButton.innerText = 'Capture Photo';
        document.body.appendChild(captureButton);

        const uploadButton = document.createElement('button');
        uploadButton.innerText = 'Upload Photo';
        document.body.appendChild(uploadButton);
    }

    attachEventListeners() {
        const captureButton = document.querySelector('button:nth-child(2)');
        const uploadButton = document.querySelector('button:nth-child(3)');

        captureButton.addEventListener('click', () => this.capturePhoto());
        uploadButton.addEventListener('click', () => this.handleUpload());
    }

    async startCamera() {
        try {
            this.stream = await navigator.mediaDevices.getUserMedia({ video: true });
            this.video.srcObject = this.stream;
            this.video.play();
        } catch (err) {
            console.error('Error accessing camera: ', err);
        }
    }

    capturePhoto() {
        this.context.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);
        const photo = this.canvas.toDataURL('image/png');
        this.showPreview(photo);
    }

    showPreview(photo) {
        const img = document.createElement('img');
        img.src = photo;
        document.body.appendChild(img);
    }

    async handleUpload() {
        const photoBlob = await this.canvas.toBlob(async (blob) => {
            const formData = new FormData();
            formData.append('file', blob);
            // Implement file upload logic here
        });
    }
}

const photoWidget = new PhotoWidget();
photoWidget.init();
photoWidget.startCamera();