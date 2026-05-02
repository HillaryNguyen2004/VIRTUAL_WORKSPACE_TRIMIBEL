/**
 * Large file upload helper - integrates chunked uploader with storage upload flow
 */

const LARGE_FILE_THRESHOLD = 50 * 1024 * 1024; 

const uploadFileChunked = async (file, folderId, conflictStrategy, fileIndex, totalFiles, onProgress) => {
    if (typeof ChunkedUploader === 'undefined') {
        throw new Error('ChunkedUploader not available - include chunked-uploader.js');
    }

    const uploader = new ChunkedUploader({
        chunkSizeMB: 5,
        maxParallelUploads: 3,
        maxRetries: 3,
    });

    try {
        const initResult = await uploader.initiateUpload(file, folderId, conflictStrategy);
        const sessionId = initResult.sessionId;

        const chunkProgress = (chunkData) => {
            onProgress?.({
                current: fileIndex + 1,
                total: totalFiles,
                fileName: file.name,
                status: 'uploading_chunk',
                uploadedChunks: chunkData.uploadedChunks,
                totalChunks: chunkData.totalChunks,
                progressPercent: chunkData.progressPercent,
            });
        };

        const result = await uploader.startUpload(sessionId, chunkProgress);
        return result;
    } catch (error) {
        throw error;
    }
};

const isLargeFile = (file) => file.size > LARGE_FILE_THRESHOLD;

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
};
