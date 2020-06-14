<template>
  <van-cell-group>
    <van-cell title="基础信息" title-class="title" />
    <van-cell title="图片" class="cell-panel">
      <ImagePanelPreview :show-delete="false" :list="info.img" />
    </van-cell>
    <van-cell title="商品标题" class="cell-panel">
      <div class="value">{{info.name}}</div>
    </van-cell>
    <van-cell title="是否上架" class="cell-panel" value-class="flex">
      <van-switch v-model="checked" size="24px" />
    </van-cell>
  </van-cell-group>
</template>

<script>
import { Switch } from "vant";
import ImagePanelPreview from "@/components/ImagePanelPreview";
export default {
  data() {
    return {};
  },
  props: {
    value: Boolean,
    info: Object
  },
  computed: {
    checked: {
      get() {
        return this.value;
      },
      set(e) {
        this.$emit("input", e);
      }
    }
  },
  components: {
    [Switch.name]: Switch,
    ImagePanelPreview
  }
};
</script>

<style scoped>
.flex {
  display: flex;
}

.title {
  font-weight: 800;
}

.value {
  height: 48px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
}
</style>