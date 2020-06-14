<template>
  <div>
    <div class="btn-box" @click="click">
      <van-icon name="play-circle-o" />
      <span class="text">{{text}}</span>
    </div>
    <van-popup v-model="popupShow" class="video-popup" @close="close">
      <VideoPlayer :options="options" ref="videoPlayer" />
    </van-popup>
  </div>
</template>

<script>
import VideoPlayer from "@/components/VideoPlayer";
export default {
  data() {
    return {
      popupShow: false,
      options: {
        height: document.body.offsetWidth,
        autoplay: this.autoplay,
        sources: [
          {
            type: "video/mp4",
            src: this.src
          }
        ]
      }
    };
  },
  props: {
    src: String,
    text: {
      type: String,
      default: "视频"
    },
    autoplay: {
      type: Boolean,
      default: true
    }
  },
  methods: {
    click() {
      this.popupShow = true;
      this.$nextTick(() => {
        this.$refs.videoPlayer.player.play();
      });
    },
    close(e) {
      this.$refs.videoPlayer.player.pause();
    }
  },
  components: {
    VideoPlayer
  }
};
</script>

<style scoped>
.btn-box {
  border-radius: 30px;
  background: rgba(0, 0, 0, 0.3);
  width: auto;
  height: 24px;
  z-index: 100;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  position: absolute;
  right: 15px;
  bottom: 15px;
  font-weight: 800;
  padding: 4px 8px;
}

.text {
  font-size: 12px;
  color: #fff;
  padding-left: 4px;
}

.video-popup {
  width: 100%;
  max-height: 100%;
  background: transparent;
}

.btn-box:active {
  background: rgba(0, 0, 0, 0.6);
}
</style>

