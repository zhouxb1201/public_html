<template>
  <videoPlayer
    ref="videoPlayer"
    class="vjs-custom-skin"
    :options="playerOptions"
    @ready="playerReadied"
    @timeupdate="onPlayerTimeupdate($event)"
    @play="onPlayerPlay($event)"
    @pause="onPlayerPause($event)"
  />
</template>

<script>
import "video.js/dist/video-js.css";
import "vue-video-player/src/custom-theme.css";
import { videoPlayer } from "vue-video-player";

const defaultOptions = {
  height: document.body.offsetWidth,
  autoplay: false,
  muted: false,
  language: "en",
  playbackRates: [0.7, 1.0, 1.5, 2.0],
  sources: [
    {
      type: "video/mp4",
      src: ""
    }
  ],
  poster: ""
};
export default {
  data() {
    return {
      playerOptions: Object.assign({ ...defaultOptions }, { ...this.options })
    };
  },
  props: {
    options: {
      type: Object,
      default: () => ({ ...defaultOptions })
    }
  },
  computed: {
    player() {
      return this.$refs.videoPlayer.player;
    }
  },
  mounted() {},
  methods: {
    playerReadied(player) {
      this.$emit("ready", player);
    },
    onPlayerTimeupdate(player) {
      this.$emit("currentTime", player.currentTime());
    },
    onPlayerPlay(player){
      this.$emit("play",player);
    },
    onPlayerPause(player){
      this.$emit("pause",player)
    }
  },
  components: {
    videoPlayer
  }
};
</script>
<style scoped>
</style>

