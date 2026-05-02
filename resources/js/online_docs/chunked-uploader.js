/**
 * ChunkedUploader - Handle large file uploads with chunking, retry, and resumable support
 * Splits files into 5MB chunks, uploads in parallel, handles resume on interruption
 */
class ChunkedUploader {
    constructor(options = {}) {
        this.chunkSizeMB = options.chunkSizeMB || 5;
        this.chunkSize = this.chunkSizeMB * 1024 * 1024;
        this.maxParallelUploads = options.maxParallelUploads || 3;
        this.maxRetries = options.maxRetries || 3;
        this.retryDelay = options.retryDelay || 1000; // ms
        
        this.sessions = new Map(); // sessionId -> session state
        this.uploadQueues = new Map(); // sessionId -> chunk queue
        this.activeUploads = new Map(); // sessionId -> number of active uploads
    }

    /**
     * Split file and initiate session
     */
    async initiateUpload(file, folderId = null, conflictStrategy = 'auto_rename') {
        try {
            // Calculate chunks
            const totalChunks = Math.ceil(file.size / this.chunkSize);
            
            // Initiate session on backend
            const response = await fetch('/online-docs/upload/initiate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('[name=csrf_token]')?.value || 
                                   document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    filename: file.name,
                    total_size: file.size,
                    total_chunks: totalChunks,
                    folder_id: folderId,
                }),
            });

            if (!response.ok) {
                throw new Error(`Failed to initiate: ${response.statusText}`);
            }

            const data = await response.json();
            const sessionId = data.session_id;

            // Initialize session state
            this.sessions.set(sessionId, {
                file,
                sessionId,
                totalChunks,
                uploadedChunks: 0,
                status: 'uploading',
                conflictStrategy,
                folderId,
            });

            // Create upload queue
            const chunks = [];
            for (let i = 1; i <= totalChunks; i++) {
                chunks.push(i);
            }
            this.uploadQueues.set(sessionId, chunks);
            this.activeUploads.set(sessionId, 0);

            return { sessionId, totalChunks, file };
        } catch (error) {
            throw new Error(`Upload initiation failed: ${error.message}`);
        }
    }

    /**
     * Start uploading chunks
     */
    async startUpload(sessionId, onProgress = null) {
        const session = this.sessions.get(sessionId);
        if (!session) {
            throw new Error('Session not found');
        }

        const queue = this.uploadQueues.get(sessionId);
        
        while (queue.length > 0) {
            // Maintain parallel upload limit
            while (this.activeUploads.get(sessionId) < this.maxParallelUploads && queue.length > 0) {
                const chunkNumber = queue.shift();
                this.uploadChunkWithRetry(sessionId, chunkNumber, onProgress);
            }

            // Wait a bit before checking again
            await new Promise(resolve => setTimeout(resolve, 100));
        }

        // Wait for all active uploads to complete
        while (this.activeUploads.get(sessionId) > 0) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }

        // All chunks uploaded, assemble
        return await this.assembleFile(sessionId);
    }

    /**
     * Upload single chunk with retry
     */
    async uploadChunkWithRetry(sessionId, chunkNumber, onProgress, retryCount = 0) {
        const session = this.sessions.get(sessionId);
        if (!session) return;

        try {
            this.activeUploads.set(sessionId, this.activeUploads.get(sessionId) + 1);
            
            const chunk = this.extractChunk(session.file, chunkNumber);
            await this.uploadChunk(sessionId, chunkNumber, chunk);

            session.uploadedChunks++;
            if (onProgress) {
                onProgress({
                    sessionId,
                    uploadedChunks: session.uploadedChunks,
                    totalChunks: session.totalChunks,
                    progressPercent: Math.round((session.uploadedChunks / session.totalChunks) * 100),
                });
            }
        } catch (error) {
            if (retryCount < this.maxRetries) {
                console.warn(`Chunk ${chunkNumber} failed, retrying... (attempt ${retryCount + 1}/${this.maxRetries})`);
                await new Promise(resolve => setTimeout(resolve, this.retryDelay * (retryCount + 1)));
                
                // Re-queue the chunk
                const queue = this.uploadQueues.get(sessionId);
                queue.push(chunkNumber);
                
                return this.uploadChunkWithRetry(sessionId, chunkNumber, onProgress, retryCount + 1);
            } else {
                throw new Error(`Chunk ${chunkNumber} upload failed after ${this.maxRetries} retries: ${error.message}`);
            }
        } finally {
            this.activeUploads.set(sessionId, this.activeUploads.get(sessionId) - 1);
        }
    }

    /**
     * Upload chunk to backend
     */
    async uploadChunk(sessionId, chunkNumber, chunkBlob) {
        const formData = new FormData();
        formData.append('session_id', sessionId);
        formData.append('chunk_number', chunkNumber);
        formData.append('chunk', chunkBlob);

        const response = await fetch('/online-docs/upload/chunk', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('[name=csrf_token]')?.value || 
                               document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: formData,
        });

        if (!response.ok) {
            throw new Error(`Chunk upload failed: ${response.statusText}`);
        }

        return await response.json();
    }

    /**
     * Extract chunk from file
     */
    extractChunk(file, chunkNumber) {
        const start = (chunkNumber - 1) * this.chunkSize;
        const end = Math.min(start + this.chunkSize, file.size);
        return file.slice(start, end);
    }

    /**
     * Get session status
     */
    async getStatus(sessionId) {
        const response = await fetch(`/online-docs/upload/status?session_id=${encodeURIComponent(sessionId)}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Failed to get status: ${response.statusText}`);
        }

        return await response.json();
    }

    /**
     * Resume incomplete upload
     */
    async resumeUpload(sessionId, file, onProgress = null) {
        try {
            const status = await this.getStatus(sessionId);
            
            if (status.status === 'completed') {
                return { status: 'already_completed' };
            }

            if (status.is_expired) {
                throw new Error('Session expired');
            }

            // Reinitialize session
            this.sessions.set(sessionId, {
                file,
                sessionId,
                totalChunks: status.total_chunks,
                uploadedChunks: status.uploaded_chunks,
                status: 'uploading',
            });

            // Queue only missing chunks
            const remainingChunks = [];
            for (let i = 1; i <= status.total_chunks; i++) {
                if (!status.uploaded_chunk_numbers.includes(i)) {
                    remainingChunks.push(i);
                }
            }
            this.uploadQueues.set(sessionId, remainingChunks);
            this.activeUploads.set(sessionId, 0);

            return await this.startUpload(sessionId, onProgress);
        } catch (error) {
            throw new Error(`Resume failed: ${error.message}`);
        }
    }

    /**
     * Assemble chunks into final file
     */
    async assembleFile(sessionId) {
        const session = this.sessions.get(sessionId);
        if (!session) {
            throw new Error('Session not found');
        }

        try {
            const response = await fetch('/online-docs/upload/assemble', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('[name=csrf_token]')?.value || 
                                   document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    conflict_strategy: session.conflictStrategy,
                }),
            });

            if (!response.ok) {
                const data = await response.json();
                throw new Error(data.message || 'Assembly failed');
            }

            const result = await response.json();
            session.status = 'completed';
            return result;
        } catch (error) {
            session.status = 'failed';
            throw new Error(`File assembly failed: ${error.message}`);
        }
    }

    /**
     * Cancel upload and cleanup
     */
    async cancelUpload(sessionId) {
        const response = await fetch('/online-docs/upload/cancel', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('[name=csrf_token]')?.value || 
                               document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
            body: JSON.stringify({ session_id: sessionId }),
        });

        if (response.ok) {
            this.sessions.delete(sessionId);
            this.uploadQueues.delete(sessionId);
            this.activeUploads.delete(sessionId);
        }

        return await response.json();
    }

    /**
     * Get formatted file size
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    }
}
