<template>
  <div class="img-group">
    <div class="item" v-for="(item,index) in list" :key="index">
      <van-icon v-if="showDelete" name="close" class="btn-delete" @click="onDelete(index)"/>
      <div class="box e-handle" @click="onPreview(index)">
        <img :src="item | BASESRC">
      </div>
    </div>
    <slot name="upload"></slot>
  </div>
</template>

<script>
import { ImagePreview } from "vant";
export default {
  data() {
    return {};
  },
  props: {
    list: {
      type: Array
    },
    showDelete: {
      type: Boolean,
      default: true
    }
  },
  methods: {
    onPreview(index) {
      ImagePreview({
        images: this.list,
        startPosition: index
      });
    },
    onDelete(index) {
      this.$Dialog
        .confirm({ showCancelButton: true, message: "确定删除该图片？" })
        .then(() => {
          this.list.splice(index, 1);
          this.$emit("delete", this.list, index);
        });
    }
  }
};
</script>

<style scoped>
.img-group {
  margin: 0 -4px;
  overflow: hidden;
}

.img-group .item {
  position: relative;
  width: calc(20% - 8px);
  float: left;
  margin: 4px;
}

.img-group .box {
  height: 0;
  width: 100%;
  padding: 50% 0;
  overflow: hidden;
  background: #f9f9f9;
}

.img-group img {
  display: block;
  width: 100%;
  margin-top: -50%;
  background-color: #fff;
  border: none;
}

.btn-delete {
  position: absolute;
  z-index: 10;
  border-radius: 50%;
  background: #fff;
  right: -5px;
  top: -5px;
}
</style>
