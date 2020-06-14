<template>
  <van-cell
    :title="item.label"
    class="cell-panel"
    :class="item.required?'van-cell--required':''"
    value-class="value-class"
  >
    <ImagePanelPreview :list="list" @delete="onDelete"/>
    <UploadImages
      multiple
      :max-num="item.max"
      :total="list.length"
      type="customform"
      @finish="onFinish"
    />
  </van-cell>
</template>

<script>
import ImagePanelPreview from "@/components/ImagePanelPreview";
import UploadImages from "@/components/UploadImages";
export default {
  name: "form-img",
  data() {
    return {
      arr: []
    };
  },
  props: {
    item: {
      type: Object
    }
  },
  computed: {
    list() {
      let value = this.item.value;
      if (value) {
        this.arr = value.split(",");
      }
      return this.arr;
    }
  },
  methods: {
    onFinish({ src }) {
      this.arr.push(src);
      this.item.value = this.arr.join(",");
    },
    onDelete(arr) {
      this.item.value = arr.join(",");
    }
  },
  components: {
    ImagePanelPreview,
    UploadImages
  }
};
</script>

<style scoped>
.value-class {
  overflow: initial;
}
</style>
