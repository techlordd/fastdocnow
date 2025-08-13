class VoiceRecorder {
    constructor() {
        this.mediaRecorder = null;
        this.audioChunks = [];
        this.isRecording = false;
        this.stream = null;
        this.recordingStartTime = null;
        this.recordingDuration = 0;
        this.maxDuration = 300; // 5 minutes in seconds
        this.timer = null;
    }

    async initialize() {
        try {
            if (!VoiceRecorder.isSupported()) {
                throw new Error(VoiceRecorder.getUnsupportedReason());
            }

            // Request microphone with fallback constraints
            let constraints = {
                audio: {
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true,
                    sampleRate: 44100,
                    sampleSize: 16,
                    channelCount: 1
                }
            };

            try {
                this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            } catch (detailedError) {
                // Try with basic constraints if advanced features fail
                console.warn('Advanced audio constraints failed, trying basic:', detailedError);
                constraints = { audio: true };
                this.stream = await navigator.mediaDevices.getUserMedia(constraints);
            }

            this.mediaRecorder = new MediaRecorder(this.stream, {
                mimeType: this.getSupportedMimeType()
            });

            this.mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    this.audioChunks.push(event.data);
                }
            };

            this.mediaRecorder.onstop = () => {
                this.handleRecordingStop();
            };

            return true;
        } catch (error) {
            console.error('Failed to initialize voice recorder:', error);
            throw error;
        }
    }

    getSupportedMimeType() {
        const types = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/mp4',
            'audio/mpeg',
            'audio/wav'
        ];

        for (const type of types) {
            if (MediaRecorder.isTypeSupported(type)) {
                return type;
            }
        }

        return '';
    }

    async startRecording() {
        if (this.isRecording) {
            return false;
        }

        try {
            if (!this.mediaRecorder) {
                await this.initialize();
            }

            this.audioChunks = [];
            this.recordingStartTime = Date.now();
            this.recordingDuration = 0;
            this.isRecording = true;

            this.mediaRecorder.start(100); // Collect data every 100ms

            // Start timer
            this.timer = setInterval(() => {
                this.recordingDuration = Math.floor((Date.now() - this.recordingStartTime) / 1000);
                this.updateRecordingUI();

                // Auto-stop if max duration reached
                if (this.recordingDuration >= this.maxDuration) {
                    this.stopRecording();
                }
            }, 100);

            this.updateRecordingUI();
            return true;
        } catch (error) {
            console.error('Failed to start recording:', error);
            this.isRecording = false;
            throw error;
        }
    }

    stopRecording() {
        if (!this.isRecording || !this.mediaRecorder) {
            return false;
        }

        this.isRecording = false;
        this.mediaRecorder.stop();

        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }

        return true;
    }

    cancelRecording() {
        if (this.isRecording) {
            this.isRecording = false;
            this.mediaRecorder.stop();
        }

        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }

        this.audioChunks = [];
        this.recordingDuration = 0;
        this.updateRecordingUI();
    }

    handleRecordingStop() {
        if (this.audioChunks.length === 0) {
            console.warn('No audio data recorded');
            return;
        }

        const audioBlob = new Blob(this.audioChunks, { 
            type: this.getSupportedMimeType() 
        });

        // Minimum recording duration (1 second)
        if (this.recordingDuration < 1) {
            console.warn('Recording too short');
            this.updateRecordingUI();
            return;
        }

        // Create audio file
        const audioFile = new File([audioBlob], `voice_message_${Date.now()}.webm`, {
            type: audioBlob.type
        });

        this.onRecordingComplete(audioFile, this.recordingDuration);
        this.updateRecordingUI();
    }

    onRecordingComplete(audioFile, duration) {
        // This will be overridden by the implementation
        console.log('Recording complete:', audioFile, duration);
    }

    updateRecordingUI() {
        // Update recording status in UI
        const event = new CustomEvent('voiceRecordingUpdate', {
            detail: {
                isRecording: this.isRecording,
                duration: this.recordingDuration,
                maxDuration: this.maxDuration
            }
        });
        document.dispatchEvent(event);
    }

    formatDuration(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    destroy() {
        if (this.isRecording) {
            this.stopRecording();
        }

        if (this.stream) {
            this.stream.getTracks().forEach(track => track.stop());
            this.stream = null;
        }

        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }

        this.mediaRecorder = null;
        this.audioChunks = [];
    }

    static isSupported() {
        // Check for basic APIs only - don't check permissions here
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return false;
        }

        if (!window.MediaRecorder) {
            return false;
        }

        // Check if running on HTTPS or localhost (required for microphone access)
        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            return false;
        }

        // Check if at least one audio mime type is supported
        const supportedTypes = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/mp4',
            'audio/mpeg',
            'audio/wav'
        ];

        return supportedTypes.some(type => MediaRecorder.isTypeSupported(type));
    }

    static getUnsupportedReason() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return 'Your browser does not support microphone access. Please use a modern browser like Chrome, Firefox, Safari, or Edge.';
        }

        if (!window.MediaRecorder) {
            return 'Your browser does not support audio recording. Please update your browser to the latest version.';
        }

        if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
            return 'Voice recording requires a secure connection (HTTPS). Please access this site over HTTPS.';
        }

        const supportedTypes = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/mp4',
            'audio/mpeg',
            'audio/wav'
        ];

        if (!supportedTypes.some(type => MediaRecorder.isTypeSupported(type))) {
            return 'Your browser does not support any of the required audio formats for recording.';
        }

        return 'Voice recording is not supported for an unknown reason.';
    }

    static async checkPermission() {
        try {
            if (!navigator.permissions) {
                // Permissions API not supported, will need to request directly
                return 'prompt';
            }

            const result = await navigator.permissions.query({ name: 'microphone' });
            return result.state; // 'granted', 'denied', or 'prompt'
        } catch (error) {
            // Fallback if permissions query fails
            return 'prompt';
        }
    }
}

// Export for use in other modules
window.VoiceRecorder = VoiceRecorder;

export default VoiceRecorder;
