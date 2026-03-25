class PhotoWidget {
    constructor(widgetElement) {
        this.widgetElement = widgetElement;
        this.cameraButton = this.widgetElement.querySelector('.camera-button');
        this.fileButton = this.widgetElement.querySelector('.file-button');
        this.previewContainer = this.widgetElement.querySelector('.preview-container');
        this.setupEventListeners();
    }

    setupEventListeners() {
        this.cameraButton.addEventListener('click', () => this.openCamera());
        this.fileButton.addEventListener('change', (event) => this.handleFileSelect(event));
    }

    async openCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            const video = document.createElement('video');
            video.srcObject = stream;
            video.play();
            this.capturePhoto(video);
        } catch (error) {
            console.error('Error accessing camera: ', error);
            alert('Camera access denied.');
        }
    }

    capturePhoto(video) {
        const canvas = document.createElement('canvas');
        const context = canvas.getContext('2d');
        const captureButton = document.createElement('button');
        captureButton.textContent = 'Capture';
        captureButton.addEventListener('click', () => {
            context.drawImage(video, 0, 0, canvas.width, canvas.height);
            const photoData = canvas.toDataURL('image/png');
            this.displayThumbnail(photoData);
            this.uploadPhoto(photoData);
        });
        this.widgetElement.appendChild(captureButton);
    }

    handleFileSelect(event) {
        const files = event.target.files;
        for (let i = 0; i < files.length; i++) {
            const reader = new FileReader();
            reader.onload = (e) => this.displayThumbnail(e.target.result);
            reader.readAsDataURL(files[i]);
        }
    }

    displayThumbnail(photoData) {
        const img = document.createElement('img');
        img.src = photoData;
        this.previewContainer.appendChild(img);
    }

    async uploadPhoto(photoData) {
        const response = await fetch('api/photo-upload.php', {
            method: 'POST',
            body: JSON.stringify({ image: photoData }),
            headers: { 'Content-Type': 'application/json' }
        });
        const progressBar = this.widgetElement.querySelector('.progress-bar');
        // Update the progress bar as needed (implementation not shown)

        if (!response.ok) {
            console.error('Error uploading photo: ', response.statusText);
        }
    }
}

// Example usage
const widgetElement = document.querySelector('.photo-widget');
const photoWidget = new PhotoWidget(widgetElement);