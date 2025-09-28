// Metered Video Meeting Functionality
// This file should only be loaded on video meeting pages

let meetingJoined = false;
let meeting;
let cameraOn = false;
let micOn = false;
let screenSharingOn = false;
let localVideoStream = null;
let activeSpeakerId = null;
let meetingInfo = {};

// Initialize Metered meeting when the SDK is loaded
function initializeMeteredMeeting() {
  if (typeof Metered === 'undefined') {
    console.error('Metered SDK not loaded');
    return;
  }
  
  meeting = new Metered.Meeting();
  initializeView();
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
  $("#cameraSelectBox").html(videoOptions.join(""));

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
  $("#microphoneSelectBox").html(audioOptions.join(""));

  /**
   * Mute/Unmute Camera and Microphone (waiting area)
   */
  $("#waitingAreaToggleMicrophone").on("click", function () {
    if (micOn) {
      micOn = false;
      $("#waitingAreaToggleMicrophone")
        .removeClass("bg-gray-500")
        .addClass("bg-gray-400");
    } else {
      micOn = true;
      $("#waitingAreaToggleMicrophone")
        .removeClass("bg-gray-400")
        .addClass("bg-gray-500");
    }
  });

  $("#waitingAreaToggleCamera").on("click", async function () {
    if (cameraOn) {
      cameraOn = false;
      $("#waitingAreaToggleCamera")
        .removeClass("bg-gray-500")
        .addClass("bg-gray-400");
      if (localVideoStream) {
        localVideoStream.getTracks().forEach((track) => track.stop());
      }
      localVideoStream = null;
      $("#waitingAreaLocalVideo")[0].srcObject = null;
    } else {
      cameraOn = true;
      $("#waitingAreaToggleCamera")
        .removeClass("bg-gray-400")
        .addClass("bg-gray-500");
      localVideoStream = await meeting.getLocalVideoStream();
      $("#waitingAreaLocalVideo")[0].srcObject = localVideoStream;
    }
  });

  /**
   * Device Change Handlers
   */
  $("#cameraSelectBox").on("change", async function () {
    const deviceId = $("#cameraSelectBox").val();
    await meeting.chooseVideoInputDevice(deviceId);
    if (cameraOn) {
      localVideoStream = await meeting.getLocalVideoStream();
      $("#waitingAreaLocalVideo")[0].srcObject = localVideoStream;
    }
  });

  $("#microphoneSelectBox").on("change", async function () {
    const deviceId = $("#microphoneSelectBox").val();
    await meeting.chooseAudioInputDevice(deviceId);
  });
}

/**
 * Join Meeting
 */
$(document).on("click", "#joinMeetingBtn", async function () {
  var username = $("#username").val();
  if (!username) {
    return alert("Please enter a username");
  }

  try {
    meetingInfo = await meeting.join({
      roomURL: `${window.METERED_DOMAIN}/${window.MEETING_ID}`,
      name: username,
    });

    console.log("Meeting joined", meetingInfo);
    $("#waitingArea").addClass("hidden");
    $("#meetingView").removeClass("hidden");
    $("#meetingAreaUsername").text(username);

    // 🔹 Force mic + cam ON when joining
    try {
      localVideoStream = await meeting.getLocalVideoStream();
      $("#localVideoTag")[0].srcObject = localVideoStream;
      await meeting.startVideo();
      await meeting.startAudio();
      micOn = true;
      cameraOn = true;
      $("#toggleCamera").addClass("bg-gray-500");
      $("#toggleMicrophone").addClass("bg-gray-500");
    } catch (err) {
      console.error("Error starting media:", err);
    }
  } catch (ex) {
    console.log("Error occurred when joining the meeting", ex);
  }
});

/**
 * Handling Meeting Events
 */
function setupMeetingEvents() {
  if (!meeting) return;
  
  meeting.on("onlineParticipants", function (participants) {
    for (let participantInfo of participants) {
      if (
        !$(`#participant-${participantInfo._id}`)[0] &&
        participantInfo._id !== meeting.participantInfo._id
      ) {
        $("#remoteParticipantContainer").append(
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
    $("#participant-" + participantInfo._id).remove();
    if (participantInfo._id === activeSpeakerId) {
      $("#activeSpeakerUsername").text("").addClass("hidden");
    }
  });

  meeting.on("remoteTrackStarted", function (remoteTrackItem) {
    $("#activeSpeakerUsername").removeClass("hidden");

    let mediaStream = new MediaStream();
    mediaStream.addTrack(remoteTrackItem.track);

    if (remoteTrackItem.type === "video") {
      if ($("#video-" + remoteTrackItem.participantSessionId)[0]) {
        $("#video-" + remoteTrackItem.participantSessionId)[0].srcObject =
          mediaStream;
        $("#video-" + remoteTrackItem.participantSessionId)[0].play();
      }
    }

    if (remoteTrackItem.type === "audio") {
      if ($("#audio-" + remoteTrackItem.participantSessionId)[0]) {
        $("#audio-" + remoteTrackItem.participantSessionId)[0].srcObject =
          mediaStream;
        $("#audio-" + remoteTrackItem.participantSessionId)[0].play();
      }
    }

    setActiveSpeaker(remoteTrackItem);
  });

  meeting.on("remoteTrackStopped", function (remoteTrackItem) {
    if (remoteTrackItem.type === "video") {
      if ($("#video-" + remoteTrackItem.participantSessionId)[0]) {
        $("#video-" + remoteTrackItem.participantSessionId)[0].srcObject =
          null;
        $("#video-" + remoteTrackItem.participantSessionId)[0].pause();
      }

      if (remoteTrackItem.participantSessionId === activeSpeakerId) {
        $("#activeSpeakerVideo")[0].srcObject = null;
        $("#activeSpeakerVideo")[0].pause();
      }
    }

    if (remoteTrackItem.type === "audio") {
      if ($("#audio-" + remoteTrackItem.participantSessionId)[0]) {
        $("#audio-" + remoteTrackItem.participantSessionId)[0].srcObject =
          null;
        $("#audio-" + remoteTrackItem.participantSessionId)[0].pause();
      }
    }
  });

  meeting.on("activeSpeaker", function (activeSpeaker) {
    setActiveSpeaker(activeSpeaker);
  });
}

function setActiveSpeaker(activeSpeaker) {
  if (activeSpeakerId != activeSpeaker.participantSessionId) {
    $(`#participant-${activeSpeakerId}`).show();
  }

  activeSpeakerId = activeSpeaker.participantSessionId;
  $(`#participant-${activeSpeakerId}`).hide();

  $("#activeSpeakerUsername").text(
    activeSpeaker.name || activeSpeaker.participant.name
  );

  if ($(`#video-${activeSpeaker.participantSessionId}`)[0]) {
    let stream = $(`#video-${activeSpeaker.participantSessionId}`)[0]
      .srcObject;
    $("#activeSpeakerVideo")[0].srcObject = stream.clone();
  }

  if (activeSpeaker.participantSessionId === meeting.participantSessionId) {
    let stream = $(`#localVideoTag`)[0].srcObject;
    if (stream) {
      $("#localVideoTag")[0].srcObject = stream.clone();
    }
  }
}

/**
 * Meeting Controls
 */
$(document).on("click", "#toggleMicrophone", async function () {
  if (micOn) {
    $("#toggleMicrophone").removeClass("bg-gray-500").addClass("bg-gray-400");
    micOn = false;
    await meeting.stopAudio();
  } else {
    $("#toggleMicrophone").removeClass("bg-gray-400").addClass("bg-gray-500");
    micOn = true;
    await meeting.startAudio();
  }
});

$(document).on("click", "#toggleCamera", async function () {
  if (cameraOn) {
    $("#toggleCamera").removeClass("bg-gray-500").addClass("bg-gray-400");
    $("#toggleScreen").removeClass("bg-gray-500").addClass("bg-gray-400");
    cameraOn = false;
    await meeting.stopVideo();
    if (localVideoStream) {
      localVideoStream.getTracks().forEach((track) => track.stop());
    }
    localVideoStream = null;
    $("#localVideoTag")[0].srcObject = null;
  } else {
    $("#toggleCamera").removeClass("bg-gray-400").addClass("bg-gray-500");
    cameraOn = true;
    await meeting.startVideo();
    localVideoStream = await meeting.getLocalVideoStream();
    $("#localVideoTag")[0].srcObject = localVideoStream;
  }
});

$(document).on("click", "#toggleScreen", async function () {
  if (screenSharingOn) {
    $("#toggleScreen").removeClass("bg-gray-500").addClass("bg-gray-400");
    screenSharingOn = false;
    await meeting.stopVideo();
    if (localVideoStream) {
      localVideoStream.getTracks().forEach((track) => track.stop());
    }
    localVideoStream = null;
    $("#localVideoTag")[0].srcObject = null;
  } else {
    $("#toggleScreen").removeClass("bg-gray-400").addClass("bg-gray-500");
    $("#toggleCamera").removeClass("bg-gray-500").addClass("bg-gray-400");
    screenSharingOn = true;
    localVideoStream = await meeting.startScreenShare();
    $("#localVideoTag")[0].srcObject = localVideoStream;
  }
});

$(document).on("click", "#leaveMeeting", async function () {
  await meeting.leaveMeeting();
  $("#meetingView").addClass("hidden");
  $("#leaveMeetingView").removeClass("hidden");
});

// Initialize when document is ready and Metered SDK is available
$(document).ready(function() {
  // Wait for Metered SDK to be available
  if (typeof Metered !== 'undefined') {
    initializeMeteredMeeting();
    setupMeetingEvents();
  } else {
    // Check every 100ms for Metered SDK
    const checkMetered = setInterval(() => {
      if (typeof Metered !== 'undefined') {
        clearInterval(checkMetered);
        initializeMeteredMeeting();
        setupMeetingEvents();
      }
    }, 100);
  }
});

// Export for global access if needed
window.MeteredMeeting = {
  initializeMeteredMeeting,
  setupMeetingEvents
};