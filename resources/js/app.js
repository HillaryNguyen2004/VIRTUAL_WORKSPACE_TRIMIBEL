import './bootstrap';
import '@fortawesome/fontawesome-free/css/all.min.css';
import jquery from 'jquery';
import axios from 'axios';
import { marked } from 'marked';
import DOMPurify from 'dompurify';

window.axios = axios;
window.marked = marked;
window.DOMPurify = DOMPurify;
// import.meta.glob('../utils/**/*.js', { eager: true });

let meetingJoined = false;
const meeting = window.Metered?.Meeting ? new window.Metered.Meeting() : null;
let cameraOn = false;
let micOn = false;
let screenSharingOn = false;
let localVideoStream = null;
let activeSpeakerId = null;
let meetingInfo = {};
let recordingActive = false;
let mediaRecorder = null;
let recordingChunks = [];
let recordingStream = null;
let recordingTimerInterval = null;
let recordingStartTime = null;
let audioContext = null;
let audioDestination = null;
const audioSources = new Map();
const remoteAudioTracks = new Map();
let recordingCanvas = null;
let recordingCanvasCtx = null;
let recordingAnimationFrame = null;

function ensureAudioContext() {
  if (!audioContext) {
    audioContext = new (window.AudioContext || window.webkitAudioContext)();
    audioDestination = audioContext.createMediaStreamDestination();
  }
}

function addAudioTrackToMix(track) {
  if (!track || audioSources.has(track.id)) return;
  ensureAudioContext();
  const trackStream = new MediaStream([track]);
  const sourceNode = audioContext.createMediaStreamSource(trackStream);
  sourceNode.connect(audioDestination);
  audioSources.set(track.id, sourceNode);
}

function removeAudioTrackFromMix(track) {
  if (!track) return;
  const sourceNode = audioSources.get(track.id);
  if (sourceNode) {
    sourceNode.disconnect();
    audioSources.delete(track.id);
  }
}

function formatDuration(ms) {
  const totalSeconds = Math.floor(ms / 1000);
  const minutes = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
  const seconds = (totalSeconds % 60).toString().padStart(2, '0');
  return `${minutes}:${seconds}`;
}

function updateRecordingUI(isRecording) {
  const btn = document.getElementById('toggleRecording');
  const timer = document.getElementById('recordingTimer');
  if (btn) {
    if (isRecording) {
      btn.classList.remove('bg-gray-700', 'hover:bg-gray-600');
      btn.classList.add('bg-red-600', 'hover:bg-red-700');
      btn.setAttribute('title', 'Stop recording');
    } else {
      btn.classList.add('bg-gray-700', 'hover:bg-gray-600');
      btn.classList.remove('bg-red-600', 'hover:bg-red-700');
      btn.setAttribute('title', 'Record meeting');
    }
  }
  if (timer) {
    if (isRecording) {
      timer.classList.remove('hidden');
    } else {
      timer.classList.add('hidden');
      timer.textContent = '00:00';
    }
  }
}

function getRecordingSourceVideoEl() {
  const activeVideo = document.getElementById('activeSpeakerVideo');
  if (activeVideo && activeVideo.srcObject) return activeVideo;
  const localVideo = document.getElementById('localVideoTag');
  if (localVideo && localVideo.srcObject) return localVideo;
  return null;
}

function setupRecordingCanvas() {
  if (!recordingCanvas) {
    recordingCanvas = document.createElement('canvas');
    recordingCanvas.width = 1280;
    recordingCanvas.height = 720;
    recordingCanvasCtx = recordingCanvas.getContext('2d');
  }
}

function startCanvasRenderLoop() {
  setupRecordingCanvas();
  const renderFrame = () => {
    if (!recordingCanvasCtx) return;
    const sourceVideo = getRecordingSourceVideoEl();
    if (sourceVideo && sourceVideo.videoWidth && sourceVideo.videoHeight) {
      if (recordingCanvas.width !== sourceVideo.videoWidth || recordingCanvas.height !== sourceVideo.videoHeight) {
        recordingCanvas.width = sourceVideo.videoWidth;
        recordingCanvas.height = sourceVideo.videoHeight;
      }
      recordingCanvasCtx.drawImage(sourceVideo, 0, 0, recordingCanvas.width, recordingCanvas.height);
    } else {
      recordingCanvasCtx.fillStyle = '#000000';
      recordingCanvasCtx.fillRect(0, 0, recordingCanvas.width, recordingCanvas.height);
    }
    recordingAnimationFrame = requestAnimationFrame(renderFrame);
  };
  recordingAnimationFrame = requestAnimationFrame(renderFrame);
}

function stopCanvasRenderLoop() {
  if (recordingAnimationFrame) {
    cancelAnimationFrame(recordingAnimationFrame);
    recordingAnimationFrame = null;
  }
}

function startRecordingTimer() {
  const timer = document.getElementById('recordingTimer');
  if (!timer) return;
  recordingStartTime = Date.now();
  timer.textContent = '00:00';
  recordingTimerInterval = setInterval(() => {
    const elapsed = Date.now() - recordingStartTime;
    timer.textContent = formatDuration(elapsed);
  }, 1000);
}

function stopRecordingTimer() {
  if (recordingTimerInterval) {
    clearInterval(recordingTimerInterval);
    recordingTimerInterval = null;
  }
  recordingStartTime = null;
}

function getSupportedRecorderOptions() {
  if (!window.MediaRecorder || !window.MediaRecorder.isTypeSupported) return undefined;
  const types = [
    'video/webm;codecs=vp9,opus',
    'video/webm;codecs=vp8,opus',
    'video/webm;codecs=vp9',
    'video/webm;codecs=vp8',
    'video/webm'
  ];
  for (const type of types) {
    if (MediaRecorder.isTypeSupported(type)) {
      return { mimeType: type };
    }
  }
  return undefined;
}

async function getLocalAudioTrackIfAvailable() {
  if (meeting && typeof meeting.getLocalAudioStream === 'function') {
    try {
      const localAudioStream = await meeting.getLocalAudioStream();
      if (localAudioStream && localAudioStream.getAudioTracks().length) {
        return localAudioStream.getAudioTracks()[0];
      }
    } catch (error) {
      console.warn('Unable to access local audio stream for recording', error);
    }
  }
  return null;
}

async function startMeetingRecording() {
  if (!window.MediaRecorder) {
    alert('Your browser does not support meeting recording.');
    return;
  }

  if (recordingActive) return;

  startCanvasRenderLoop();
  let videoTrack = null;
  if (recordingCanvas && recordingCanvas.captureStream) {
    const canvasStream = recordingCanvas.captureStream(30);
    if (canvasStream && canvasStream.getVideoTracks().length) {
      videoTrack = canvasStream.getVideoTracks()[0];
    }
  }

  ensureAudioContext();
  if (audioContext && audioContext.state === 'suspended') {
    await audioContext.resume();
  }

  const localAudioTrack = await getLocalAudioTrackIfAvailable();
  if (localAudioTrack) {
    addAudioTrackToMix(localAudioTrack);
  }

  for (const track of remoteAudioTracks.values()) {
    addAudioTrackToMix(track);
  }

  recordingStream = new MediaStream();
  if (videoTrack) {
    recordingStream.addTrack(videoTrack);
  }

  const mixedAudioTrack = audioDestination?.stream?.getAudioTracks?.()[0];
  if (mixedAudioTrack) {
    recordingStream.addTrack(mixedAudioTrack);
  }

  if (recordingStream.getTracks().length === 0) {
    alert('No media tracks available to record.');
    return;
  }

  const options = getSupportedRecorderOptions();
  mediaRecorder = options ? new MediaRecorder(recordingStream, options) : new MediaRecorder(recordingStream);
  recordingChunks = [];

  mediaRecorder.ondataavailable = (event) => {
    if (event.data && event.data.size > 0) {
      recordingChunks.push(event.data);
    }
  };

  mediaRecorder.onstop = () => {
    stopRecordingTimer();
    updateRecordingUI(false);
    recordingActive = false;
    stopCanvasRenderLoop();

    if (recordingChunks.length) {
      const blobType = recordingChunks[0]?.type || 'video/webm';
      const recordingBlob = new Blob(recordingChunks, { type: blobType });
      const downloadUrl = URL.createObjectURL(recordingBlob);
      const anchor = document.createElement('a');
      const safeTimestamp = new Date().toISOString().replace(/[:.]/g, '-');
      anchor.href = downloadUrl;
      anchor.download = `meeting-${window.MEETING_ID || 'recording'}-${safeTimestamp}.webm`;
      document.body.appendChild(anchor);
      anchor.click();
      anchor.remove();
      setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
    }

    recordingChunks = [];
    recordingStream = null;
    mediaRecorder = null;

    for (const sourceNode of audioSources.values()) {
      sourceNode.disconnect();
    }
    audioSources.clear();
  };

  mediaRecorder.start(1000);
  recordingActive = true;
  updateRecordingUI(true);
  startRecordingTimer();
}

function stopMeetingRecording() {
  if (!recordingActive) return;
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.stop();
  }
}

function initMeetingChat() {
  const chatContainer = document.getElementById('meetingChatMessages');
  const chatInput = document.getElementById('meetingChatInput');
  const chatSend = document.getElementById('meetingChatSend');
  const chatStatus = document.getElementById('meetingChatStatus');

  if (!chatContainer || !chatInput || !chatSend) return;

  const appendMessage = (sender, message, isLocal = false) => {
    if (!message) return;
    const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const wrapper = document.createElement('div');
    wrapper.className = isLocal ? 'flex flex-col items-end' : 'flex flex-col items-start';
    wrapper.innerHTML = `
      <div class="max-w-[85%] rounded-2xl px-3 py-2 text-sm ${isLocal ? 'bg-emerald-600 text-white' : 'bg-gray-900 text-gray-100'}">
        <div class="text-[11px] font-semibold ${isLocal ? 'text-emerald-100' : 'text-gray-400'}">${sender}</div>
        <div class="mt-0.5 break-words">${DOMPurify.sanitize(message)}</div>
      </div>
      <div class="text-[10px] text-gray-500 mt-1">${time}</div>
    `;
    chatContainer.appendChild(wrapper);
    chatContainer.scrollTop = chatContainer.scrollHeight;
  };

  const sendMessage = async () => {
    const text = chatInput.value.trim();
    if (!text) return;

    const senderName = meeting?.participantInfo?.name || document.getElementById('localUsername')?.textContent?.trim() || 'You';

    chatInput.value = '';
    appendMessage(senderName, text, true);

    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      if (csrfToken) {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
      }
      await axios.post(`/meeting/${window.MEETING_ID}/chat`, { message: text });
    } catch (error) {
      console.warn('Failed to send meeting chat message', error);
    }
  };

  chatSend.addEventListener('click', sendMessage);
  chatInput.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      sendMessage();
    }
  });

  if (window.Echo && window.MEETING_ID) {
    try {
      window.Echo.private(`meeting.${window.MEETING_ID}`)
        .listen('.meeting.chat', (payload) => {
          const sender = payload?.name || 'Participant';
          const message = payload?.message || '';
          appendMessage(sender, message, false);
        });

      if (chatStatus) {
        chatStatus.textContent = 'Live';
        chatStatus.classList.remove('text-gray-500');
        chatStatus.classList.add('text-emerald-400');
      }
    } catch (error) {
      console.warn('Failed to subscribe to meeting chat', error);
      if (chatStatus) {
        chatStatus.textContent = 'Offline';
        chatStatus.classList.remove('text-emerald-400');
        chatStatus.classList.add('text-gray-500');
      }
    }
  } else if (chatStatus) {
    chatStatus.textContent = 'Offline';
  }
}

async function recordMeetingLeave() {
  if (!window.MEETING_ID) return;
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
  }

  try {
    await axios.post('/meetings/history/leave', {
      meeting_id: window.MEETING_ID,
    });
  } catch (error) {
    console.warn('Failed to record meeting leave', error);
  }
}

// Add this to your app.js, at the top level
jquery(function() {
    const urlParams = new URLSearchParams(window.location.search);
    const username = window.CURRENT_USER_NAME || urlParams.get('username');

    if (username && jquery("#meetingView").length > 0) {
        console.log("Auto-joining meeting as:", username);
        joinMeetingFromUrl(username);
    }
});

// This is your auto-join function
async function joinMeetingFromUrl(username) {
  // try {
  //   meeting = new Metered.Meeting(); 
  //   meetingInfo = await meeting.join({
  //     roomURL: `${window.METERED_DOMAIN}/${window.MEETING_ID}`,
  //     name: username,
  //   });

  //   console.log("Meeting joined", meetingInfo);
  //   jquery("#waitingArea").addClass("hidden");
  //   jquery("#meetingView").removeClass("hidden");
  //   jquery("#meetingAreaUsername").text(username);

  //   // 🔹 Force mic + cam ON when joining
  //   try {
  //     localVideoStream = await meeting.getLocalVideoStream();
  //     jquery("#localVideoTag")[0].srcObject = localVideoStream;
  //     await meeting.startVideo();
  //     await meeting.startAudio();
  //     micOn = true;
  //     cameraOn = true;
  //     jquery("#toggleCamera").addClass("bg-gray-500");
  //     jquery("#toggleMicrophone").addClass("bg-gray-500");
  //   } catch (err) {
  //     console.error("Error starting media:", err);
  //   }
  // } catch (ex) {
  //   console.log("Error occurred when joining the meeting", ex);
  // }
  try {
      // meeting = new Metered.Meeting(); 
      meetingInfo = await meeting.join({
          roomURL: `${window.METERED_DOMAIN}/${window.MEETING_ID}`,
          name: username,
      });

      console.log("Meeting joined automatically", meetingInfo);
      jquery("#localUsername").text(username);

      const localVideoStream = await meeting.getLocalVideoStream();
      jquery("#localVideoTag")[0].srcObject = localVideoStream;
      await meeting.startVideo();
      await meeting.startAudio();

      micOn = true;
      cameraOn = true;
      
      jquery("#toggleCamera").addClass("bg-gray-500");
      jquery("#toggleMicrophone").addClass("bg-gray-500");

        initMeetingChat();

  } catch (ex) {
      console.log("Error auto-joining meeting", ex);
      alert("Error joining meeting. Please try again.");
  }
}

async function initializeView() {
  if (!meeting) {
    return;
  }
  /**
   * Populating the cameras
   */
  const videoInputDevices = await meeting.listVideoInputDevices();
  const videoOptions = [];
  for (let item of videoInputDevices) {
    videoOptions.push(
      `<option value="${item.deviceId}">${item.label}</option>`
    );
  }
  cameraOn = true;
  jquery("#cameraSelectBox").html(videoOptions.join(""));


  /**
   * Populating Microphones
   */
  const audioInputDevices = await meeting.listAudioInputDevices();
  const audioOptions = [];
  for (let item of audioInputDevices) {
    audioOptions.push(
      `<option value="${item.deviceId}">${item.label}</option>`
    );
  }
  micOn = true;
  jquery("#microphoneSelectBox").html(audioOptions.join(""));
  

  /**
   * Mute/Unmute Camera and Microphone (waiting area)
   */
  jquery("#waitingAreaToggleMicrophone").on("click", function () {
    if (micOn) {
      micOn = false;
      // jquery("#waitingAreaToggleMicrophone")
      //   .removeClass("bg-gray-500")
      //   .addClass("bg-gray-400");
      jquery(this).find(".mic-on").addClass("hidden");
      jquery(this).find(".mic-off").removeClass("hidden");
      jquery(this)
        .removeClass("bg-gray-700/70")
        .addClass("bg-red-600");
    } else {
      micOn = true;
      // jquery("#waitingAreaToggleMicrophone")
      //   .removeClass("bg-gray-400")
      //   .addClass("bg-gray-500");
      jquery(this).find(".mic-on").removeClass("hidden");
      jquery(this).find(".mic-off").addClass("hidden");
      jquery(this)
        .removeClass("bg-red-600")
        .addClass("bg-gray-700/70");
    }
  });

  jquery("#waitingAreaToggleCamera").on("click", async function () {
    if (cameraOn) {
      cameraOn = false;
      // jquery("#waitingAreaToggleCamera")
      //   .removeClass("bg-gray-500")
      //   .addClass("bg-gray-400");
      jquery(this).find(".cam-on").addClass("hidden");
      jquery(this).find(".cam-off").removeClass("hidden");
      jquery(this)
        .removeClass("bg-gray-700/70")
        .addClass("bg-red-600");
      if (localVideoStream) {
        localVideoStream.getTracks().forEach((track) => track.stop());
      }
      localVideoStream = null;
      jquery("#waitingAreaLocalVideo")[0].srcObject = null;
    } else {
      cameraOn = true;
      // jquery("#waitingAreaToggleCamera")
      //   .removeClass("bg-gray-400")
      //   .addClass("bg-gray-500");
      jquery(this).find(".cam-on").removeClass("hidden");
      jquery(this).find(".cam-off").addClass("hidden");
      jquery(this)
        .removeClass("bg-red-600")
        .addClass("bg-gray-700/70");
      localVideoStream = await meeting.getLocalVideoStream();
      jquery("#waitingAreaLocalVideo")[0].srcObject = localVideoStream;
    }
  });

  /**
   * Device Change Handlers
   */
  jquery("#cameraSelectBox").on("change", async function () {
    const deviceId = jquery("#cameraSelectBox").val();
    await meeting.chooseVideoInputDevice(deviceId);
    if (cameraOn) {
      localVideoStream = await meeting.getLocalVideoStream();
      jquery("#waitingAreaLocalVideo")[0].srcObject = localVideoStream;
    }
  });

  jquery("#microphoneSelectBox").on("change", async function () {
    const deviceId = jquery("#microphoneSelectBox").val();
    await meeting.chooseAudioInputDevice(deviceId);
  });
}
if (meeting) {
  initializeView();
}

/**
 * Join Meeting
 */
jquery("#joinMeetingBtn").on("click", async function () {
  const currentPath = window.location.pathname;

  if (!window.CURRENT_USER_NAME) {
    return alert("Please login to join the meeting.");
  }

  const newWindowUrl = `${currentPath}/room`;
  window.open(newWindowUrl, '_blank');
});

if (document.getElementById('meetingView')) {
  initMeetingChat();
}

/**
 * Handling Meeting Events
 */
if (meeting) {
  meeting.on("onlineParticipants", function (participants) {
  for (let participantInfo of participants) {
    if (
      !jquery(`#participant-${participantInfo._id}`)[0] &&
      participantInfo._id !== meeting.participantInfo._id
    ) {
      jquery("#remoteParticipantContainer").append(
        `
        <div id="participant-${participantInfo._id}" class="w-48 h-48 rounded-3xl bg-gray-900 relative">
          <video id="video-${participantInfo._id}" autoplay class="object-contain w-full rounded-t-3xl"></video>
          <video id="audio-${participantInfo._id}" autoplay class="hidden"></video>
          <div class="absolute h-8 w-full bg-gray-700 rounded-b-3xl bottom-0 text-white text-center font-bold pt-1">
              ${participantInfo.name}
          </div>
        </div>
        `
      );
    }

  }
  });

  meeting.on("participantLeft", function (participantInfo) {
  jquery("#participant-" + participantInfo._id).remove();
  if (participantInfo._id === activeSpeakerId) {
    jquery("#activeSpeakerUsername").text("").addClass("hidden");
  }
  });

  meeting.on("remoteTrackStarted", function (remoteTrackItem) {
  jquery("#activeSpeakerUsername").removeClass("hidden");

  let mediaStream = new MediaStream();
  mediaStream.addTrack(remoteTrackItem.track);

  if (remoteTrackItem.type === "video") {
    if (jquery("#video-" + remoteTrackItem.participantSessionId)[0]) {
      jquery("#video-" + remoteTrackItem.participantSessionId)[0].srcObject =
        mediaStream;
      jquery("#video-" + remoteTrackItem.participantSessionId)[0].play();
    }
  }

  if (remoteTrackItem.type === "audio") {
    if (remoteTrackItem.track) {
      remoteAudioTracks.set(remoteTrackItem.track.id, remoteTrackItem.track);
      if (recordingActive) {
        addAudioTrackToMix(remoteTrackItem.track);
      }
    }
    if (jquery("#audio-" + remoteTrackItem.participantSessionId)[0]) {
      jquery("#audio-" + remoteTrackItem.participantSessionId)[0].srcObject =
        mediaStream;
      jquery("#audio-" + remoteTrackItem.participantSessionId)[0].play();
    }
  }

  setActiveSpeaker(remoteTrackItem);
  });

  meeting.on("remoteTrackStopped", function (remoteTrackItem) {
  if (remoteTrackItem.type === "video") {
    if (jquery("#video-" + remoteTrackItem.participantSessionId)[0]) {
      jquery("#video-" + remoteTrackItem.participantSessionId)[0].srcObject =
        null;
      jquery("#video-" + remoteTrackItem.participantSessionId)[0].pause();
    }

    if (remoteTrackItem.participantSessionId === activeSpeakerId) {
      jquery("#activeSpeakerVideo")[0].srcObject = null;
      jquery("#activeSpeakerVideo")[0].pause();
    }
  }

  if (remoteTrackItem.type === "audio") {
    if (remoteTrackItem.track) {
      remoteAudioTracks.delete(remoteTrackItem.track.id);
      removeAudioTrackFromMix(remoteTrackItem.track);
    }
    if (jquery("#audio-" + remoteTrackItem.participantSessionId)[0]) {
      jquery("#audio-" + remoteTrackItem.participantSessionId)[0].srcObject =
        null;
      jquery("#audio-" + remoteTrackItem.participantSessionId)[0].pause();
    }
  }
  });

  meeting.on("activeSpeaker", function (activeSpeaker) {
  setActiveSpeaker(activeSpeaker);
});

function setActiveSpeaker(activeSpeaker) {
  if (activeSpeakerId != activeSpeaker.participantSessionId) {
    jquery(`#participant-${activeSpeakerId}`).show();
  }

  activeSpeakerId = activeSpeaker.participantSessionId;
  jquery(`#participant-${activeSpeakerId}`).hide();

  jquery("#activeSpeakerUsername").text(
    activeSpeaker.name || activeSpeaker.participant.name
  );

  if (jquery(`#video-${activeSpeaker.participantSessionId}`)[0]) {
    let stream = jquery(`#video-${activeSpeaker.participantSessionId}`)[0]
      .srcObject;
    jquery("#activeSpeakerVideo")[0].srcObject = stream.clone();
  }

  if (activeSpeaker.participantSessionId === meeting.participantSessionId) {
    let stream = jquery(`#localVideoTag`)[0].srcObject;
    if (stream) {
      jquery("#localVideoTag")[0].srcObject = stream.clone();
    }
  }
}

/**
 * Meeting Controls
 */
jquery("#toggleMicrophone").on("click", async function () {
  if (micOn) {
    jquery(this).find(".mic-on").addClass("hidden");
    jquery(this).find(".mic-off").removeClass("hidden");
    jquery(this)
      .removeClass("bg-gray-600")
      .addClass("bg-red-800")
      .removeClass("hover:bg-gray-500")
      .addClass("hover:bg-red-900");
    micOn = false;
    await meeting.stopAudio();
  } else {
    // jquery("#toggleMicrophone").removeClass("bg-gray-400").addClass("bg-gray-500");
    jquery(this).find(".mic-on").removeClass("hidden");
    jquery(this).find(".mic-off").addClass("hidden");
    jquery(this)
      .removeClass("bg-red-800")
      .addClass("bg-gray-600")
      .removeClass("hover:bg-red-900")
      .addClass("hover:bg-gray-500");
    micOn = true;
    await meeting.startAudio();
  }
});

jquery("#toggleCamera").on("click", async function () {
    if (!screenSharingOn){
        if (cameraOn) {
            jquery(this).find(".cam-on").addClass("hidden");
            jquery(this).find(".cam-off").removeClass("hidden");
            jquery(this)
                .removeClass("bg-gray-600")
                .addClass("bg-red-800")
                .removeClass("hover:bg-gray-500")
                .addClass("hover:bg-red-900");
            cameraOn = false;
            await meeting.stopVideo();
            if (localVideoStream) {
            localVideoStream.getTracks().forEach((track) => track.stop());
            }
            localVideoStream = null;
            jquery("#localVideoTag")[0].srcObject = null;
        } else {
            jquery(this).find(".cam-on").removeClass("hidden");
            jquery(this).find(".cam-off").addClass("hidden");
            jquery(this)
                .removeClass("bg-red-800")
                .addClass("bg-gray-600")
                .removeClass("hover:bg-red-900")
                .addClass("hover:bg-gray-500");
            cameraOn = true;
            await meeting.startVideo();
            localVideoStream = await meeting.getLocalVideoStream();
            jquery("#localVideoTag")[0].srcObject = localVideoStream;
        }
    }
});

jquery("#toggleScreen").on("click", async function () {
  if (screenSharingOn) {
    jquery(this)
      .removeClass("bg-indigo-500")
      .addClass("bg-gray-600")
      .removeClass("hover:bg-indigo-700")
      .addClass("hover:bg-gray-500");
    
    screenSharingOn = false;

    // Camera on indicator
    if (cameraOn == true) {
      jquery("#toggleCamera").find(".cam-off").addClass("hidden");
      jquery("#toggleCamera").find(".cam-on").removeClass("hidden");
      jquery("#toggleCamera")
        .removeClass("bg-red-800")
        .addClass("bg-gray-600")
        .removeClass("hover:bg-red-900")
        .addClass("hover:bg-gray-500");
      cameraOn = true;
      await meeting.stopVideo();
      await meeting.startVideo();
      localVideoStream = await meeting.getLocalVideoStream();
      jquery("#localVideoTag")[0].srcObject = localVideoStream;
    } else {
      await meeting.stopVideo();
      if (localVideoStream) {
        localVideoStream.getTracks().forEach((track) => track.stop());
      }
      localVideoStream = null;
      jquery("#localVideoTag")[0].srcObject = null;
    }
  } else {
    jquery(this)
      .removeClass("bg-gray-600")
      .addClass("bg-indigo-500")
      .removeClass("hover:bg-gray-500")
      .addClass("hover:bg-indigo-700");

    // Camera off indicator
    if (cameraOn == true) {
        jquery("#toggleCamera").find(".cam-on").addClass("hidden");
        jquery("#toggleCamera").find(".cam-off").removeClass("hidden");
        jquery("#toggleCamera")
            .removeClass("bg-gray-600")
            .addClass("bg-red-800")
            .removeClass("hover:bg-gray-500")
            .addClass("hover:bg-red-900");
        
        await meeting.stopVideo();
    }

    

    screenSharingOn = true;
    // cameraOn = false;
    await meeting.startVideo();
    localVideoStream = await meeting.startScreenShare();
    jquery("#localVideoTag")[0].srcObject = localVideoStream;
  }
});

jquery("#toggleRecording").on("click", async function () {
  if (recordingActive) {
    stopMeetingRecording();
  } else {
    await startMeetingRecording();
  }
});

jquery("#leaveMeeting").on("click", async function () {
  if (recordingActive) {
    stopMeetingRecording();
  }
  await recordMeetingLeave();
  await meeting.leaveMeeting();
  jquery("#meetingView").addClass("hidden");
  jquery("#leaveMeetingView").removeClass("hidden");
  });
}