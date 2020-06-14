<template>
  <div class="vedio-wrap" >
    <!-- <div class="btn-box" @click="onClikcPlay"></div> -->
    <VideoPlayer :options="options" ref="videoPlayer" @play="onPlayRun" @pause="onPauseRun" />
  </div>
</template>

<script>
import VideoPlayer from "@/components/VideoPlayer";
export default {
  data() {
    return {
      options: {
        height: document.body.offsetHeight,
        autoplay: this.autoplay,
        sources: [
          {
            type: "video/mp4",
            src: this.src
          }
        ],
        playsinline: true,
        poster:this.poster
      },
      is_play: false //是否正在播放
    };
  },
  props: {
    src: String,
    autoplay: {
      type: Boolean,
      default: true
    },
    poster:String
  },
  methods: {
    start() {
      this.$nextTick(() => {
        this.$refs.videoPlayer.player.play();
      });
    },
    stop() {
      this.$refs.videoPlayer.player.pause();
    },
    onClikcPlay() {
      if (this.is_play) {
        this.stop();
      } else {
        this.start();
      }
    },
    onPlayRun(player) {
      this.is_play = true;
    },
    onPauseRun(player) {
      this.is_play = false;
    }
  },
  components: {
    VideoPlayer
  }
};
</script>

<style scoped>
.vedio-wrap {
  position: relative;
}
.vedio-wrap >>> .vjs-big-play-button {
  width: 2em;
  border-radius: 50%;
  border: 1px solid #ffffff;
  left: 50%;
  transform: translateX(-50%);
  margin-left: 0px;
  margin-top: 0px;
}
.vedio-wrap >>> .vjs-control-bar {
  display: none;
}
.vedio-wrap >>> .vjs-loading-spinner {
  border: 1px solid rgba(0, 0, 0, 0.45);
  border-color: rgba(0, 0, 0, 0.45);
}
.btn-box {
  width: 100%;
  height: 50%;
  z-index: 9999;
  align-items: center;
  justify-content: center;
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -56%);
}
</style>