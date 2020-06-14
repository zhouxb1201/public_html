<template>
  <div class="poster" ref="poster">
    <img :src="$BASEIMGPATH+'poster.jpg'" id="posterBg" class="poster-bg" />
    <div class="poster-qr-box">
      <Qrcode :options="{ size }" :value="qrUrl" tag="img" class="poster-qr" id="qrImg" />
    </div>
  </div>
</template>

<script>
import Qrcode from "./Qrcode";
export default {
  data() {
    return {};
  },
  props: {
    qrUrl: String,
    loaded: Function
  },
  computed: {
    size() {
      return document.body.offsetWidth / 2;
    }
  },
  mounted() {
    const $this = this;
    Promise.all([this.loadedQr(), this.loadedPosterBg()])
      .then(() => {
        $this.loaded();
      })
      .catch(() => {});
  },
  methods: {
    loadedQr() {
      return new Promise((reslove, reject) => {
        document.getElementById("qrImg").onload = function(e) {
          reslove(e);
        };
      });
    },
    loadedPosterBg() {
      return new Promise((reslove, reject) => {
        document.getElementById("posterBg").onload = function(e) {
          reslove(e);
        };
      });
    }
  },
  components: {
    Qrcode
  }
};
</script>

<style scoped>
.poster {
  position: relative;
}

.poster img {
  max-width: 100%;
  display: block;
}

.poster .poster-qr-box {
  position: absolute;
  top: 22%;
  left: 50%;
  height: 0;
  width: 36%;
  padding: 18% 0;
  overflow: hidden;
  transform: translate(-50%, 0%);
}

.poster .poster-qr {
  height: auto;
  display: block;
  width: 100%;
  margin-top: -50%;
  background-color: #fff;
  border: none;
}
</style>
