
var oBtn = document.getElementById("btn");
var oW, oLeft;
var oSlider = document.getElementById("slider");
var oTrack = document.getElementById("track");
var oIcon = document.getElementById("icon");
var oSpinner = document.getElementById("spinner");
var flag = 1;
oBtn.addEventListener("touchstart",function(e) {
    if (flag == 1) {
      var touches = e.touches[0];
      oW = touches.clientX - oBtn.offsetLeft;
      oBtn.className = "button";
      oTrack.className = "track";
    }
  },false);
oBtn.addEventListener("touchmove",function(e) {
    if (flag == 1) {
      var touches = e.touches[0];
      oLeft = touches.clientX - oW;
      if (oLeft < 0) {
        oLeft = 0;
      } else if (oLeft > document.documentElement.clientWidth - oBtn.offsetWidth - 30) {
        oLeft = document.documentElement.clientWidth - oBtn.offsetWidth - 30;
      }
      oBtn.style.left = oLeft + "px";
      oTrack.style.width = oLeft + "px";
    }
  },
  false
);
oBtn.addEventListener(
  "touchend",
  function() {
    if (oLeft >= oSlider.clientWidth - oBtn.clientWidth) {
      oBtn.style.left = document.documentElement.clientWidth - oBtn.offsetWidth - 30;
      oTrack.style.width = document.documentElement.clientWidth - oBtn.offsetWidth - 30;
      oIcon.style.display = "none";
      oSpinner.style.display = "block";
      flag = 0;
    } else {
      oBtn.style.left = 0;
      oTrack.style.width = 0;
    }
    oBtn.className = "button-on";
    oTrack.className = "track-on";
    if(oBtn.classList.contains('button-on')){
      $('#submit').removeClass('disaleds');
    }
  },
  false
);
