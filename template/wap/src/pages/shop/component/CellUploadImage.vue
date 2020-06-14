<template>
  <van-cell :title="label" class="cell-panel van-cell--required">
    <ImagePanelPreview :list="arr" @delete="onDelete"/>
    <UploadImages max-num="1" :total="arr.length" @finish="onFinish" v-if="!arr.length"/>
    <span class="a-link example-text" @click="showExamplePopup = true" v-if="showExample">示例</span>
    <van-popup v-model="showExamplePopup" class="popup-box">
      <img class="example-img" :src="exampleImg">
    </van-popup>
  </van-cell>
</template>

<script>
import ImagePanelPreview from "@/components/ImagePanelPreview";
import UploadImages from "@/components/UploadImages";
export default {
  props: {
    value: String,
    label: String,
    showExample: {
      type: Boolean,
      default: false
    },
    exampleImg: String
  },
  data() {
    return {
      arr: [],
      showExamplePopup: false
    };
  },
  methods: {
    onFinish({ src }) {
      this.arr.push(src);
      this.$emit("input", this.arr.join(","));
    },
    onDelete(arr) {
      this.$emit("input", arr.join(","));
    }
  },
  components: {
    ImagePanelPreview,
    UploadImages
  }
};
</script>

<style scoped>
.example-text {
  position: absolute;
  right: 0;
  top: 0;
  z-index: 10;
}

.popup-box {
  width: 80%;
  max-height: 60%;
}

.example-img {
  max-width: 100%;
  display: block;
  height: auto;
}
</style>
