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
const meeting = new Metered.Meeting();
let cameraOn = false;
let micOn = false;
let screenSharingOn = false;
let localVideoStream = null;
let activeSpeakerId = null;
let meetingInfo = {};

// Add this to your app.js, at the top level
jquery(function() {
    
    const urlParams = new URLSearchParams(window.location.search);
    const username = urlParams.get('username');

    if (username && jquery("#meetingView").length > 0) {
        console.log("Username found, auto-joining meeting...");
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

  } catch (ex) {
      console.log("Error auto-joining meeting", ex);
      alert("Error joining meeting. Please try again.");
  }
}

async function initializeView() {
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
initializeView();

/**
 * Join Meeting
 */
jquery("#joinMeetingBtn").on("click", async function () {
  var username = jquery("#username").val();
  if (!username) {
    return alert("Please enter a username");
  }

  // Get the base path (e.g., /meeting/room_id)
  const currentPath = window.location.pathname;

  // Build the NEW URL for the meeting room page
  const newWindowUrl = `${currentPath}/room?username=${encodeURIComponent(username)}`;

  // Open the new window
  window.open(newWindowUrl, '_blank');
});

/**
 * Handling Meeting Events
 */
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

jquery("#leaveMeeting").on("click", async function () {
  await meeting.leaveMeeting();
  jquery("#meetingView").addClass("hidden");
  jquery("#leaveMeetingView").removeClass("hidden");
});