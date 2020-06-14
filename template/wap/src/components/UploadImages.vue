<template>
  <van-uploader
    :after-read="onRead"
    :name="name"
    :multiple="multiple"
    :disabled="isDisabled"
    v-if="!isDisabled"
  >
    <slot>
      <div class="uploader" :class="uploaderClass">
        <van-icon name="photograph" class="upload-icon"/>
        <div>{{total}}/{{maxNum}}</div>
      </div>
    </slot>
  </van-uploader>
</template>

<script>
import { Uploader } from "vant";
export default {
  data() {
    return {
      arrLength: 0
    };
  },
  props: {
    multiple: {
      type: [Boolean],
      default: false
    },
    maxNum: {
      type: [String, Number],
      default: 5
    },
    maxSize: {
      type: Number,
      default: 5120
    },
    name: {
      type: String
    },
    total: {
      type: Number,
      default: 0
    },
    /**
     * 上传类型
     * evaluate 订单评价
     * customform 自定义表单
     * avatar 头像
     */
    type: String
  },
  watch: {
    total(e) {
      this.arrLength = e;
    }
  },
  computed: {
    isDisabled() {
      if (!this.multiple) return false;
      return this.total >= this.maxNum ? true : false;
    },
    uploaderClass() {
      return this.isDisabled ? "uploader-disabled" : "";
    }
  },
  methods: {
    sizeToM(size) {
      return parseInt(size) / 1024 + "M";
    },
    onRead(data, { name }) {
      const $this = this;
      const maxSize = $this.maxSize;
      let arr = [];
      if (data.length) {
        data.forEach(({ file }) => {
          arr.push(file);
        });
      } else {
        arr.push(data.file);
      }
      if (this.multiple) {
        if (arr.length > $this.maxNum) {
          return $this.$Toast(`最多可以选择${$this.maxNum}张图片！`);
        }
        if ($this.arrLength + arr.length > $this.maxNum) {
          var exceedNum = $this.arrLength + arr.length - $this.maxNum; //超出张数
          var retainNum = arr.length - exceedNum; //保留张数
          arr = arr.filter((e, i) => i < retainNum);
          // $this.$Toast(`你超出了${exceedNum}张`);
        }
      }
      arr.forEach(file => {
        let fileSize = file.size / 1024;
        if (fileSize > maxSize) {
          $this.$Toast(
            `${file.name}图片大小不能超过${this.sizeToM(this.maxSize)}`
          );
        } else {
          let param = new FormData();
          param.append("file", file);
          if ($this.type) param.append("type", $this.type);
          $this.onUploadImages(param, name);
        }
      });
    },
    onUploadImages(param, name) {
      const $this = this;
      $this.$Toast.allowMultiple();
      const loading = $this.$Toast.loading({
        message: "上传中...",
        duration: 0,
        forbidClick: true,
        loadingType: "circular"
      });
      $this.$store
        .dispatch("uploadImages", param)
        .then(({ data }) => {
          loading.clear();
          $this.$emit("finish", data, $this.name);
          $this.$Toast.success("上传成功！");
        })
        .catch(error => {
          loading.clear();
          $this.$emit("fail", error, $this.name);
          $this.$Toast.fail("上传失败！");
        });
    }
  },
  components: {
    [Uploader.name]: Uploader
  }
};
</script>
<style scoped>
input[type="file" i] {
  align-items: baseline;
  color: inherit;
  text-align: start !important;
}

.uploader {
  position: relative;
  display: flex;
  flex-flow: column;
  width: 40px;
  height: 40px;
  text-align: center;
  font-size: 12px;
  color: #666;
  border: 1px dashed #ddd;
  line-height: 1.2;
}

.uploader:active {
  background: rgba(0, 0, 0, 0.05);
}

.upload-icon {
  font-size: 24px;
}

.uploader-disabled {
  background: #f5f5f5;
}
</style>
