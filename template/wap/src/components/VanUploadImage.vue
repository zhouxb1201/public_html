<template>
  <div class="uploader van-uploader" :class="disabled?'uploader-disabled':''">
    <slot/>
    <input
      v-bind="$attrs"
      ref="input"
      type="file"
      class="van-uploader__input"
      :accept="accept"
      :disabled="disabled"
      @change="onChange($event)"
    >
  </div>
</template>

<script>
export default {
  inheritAttrs: false,

  props: {
    disabled: Boolean,
    beforeRead: Function,
    afterRead: Function,
    accept: {
      type: String,
      default: "image/*"
    },
    resultType: {
      type: String,
      default: "dataUrl"
    },
    maxSize: {
      type: Number,
      default: Number.MAX_VALUE
    },
    index: {
      type: Number,
      default: 0
    }
  },

  computed: {
    showNativeInput() {
      return !(
        this.$store.state.isWeixin && this.$store.getters.config.is_wchat
      );
    }
  },

  methods: {
    onChange(event) {
      let { files } = event.target;
      if (this.disabled || !files.length) {
        return;
      }

      files = files.length === 1 ? files[0] : [].slice.call(files, 0);
      if (!files || (this.beforeRead && !this.beforeRead(files))) {
        return;
      }

      if (Array.isArray(files)) {
        Promise.all(files.map(this.readFile)).then(contents => {
          let oversize = false;
          const payload = files.map((file, index) => {
            if (file.size > this.maxSize) {
              oversize = true;
            }

            return {
              file: files[index],
              content: contents[index]
            };
          });

          this.onAfterRead(payload, oversize);
        });
      } else {
        this.readFile(files).then(content => {
          this.onAfterRead(
            {
              file: files,
              content
            },
            files.size > this.maxSize
          );
        });
      }
    },

    readFile(file) {
      return new Promise(resolve => {
        const reader = new FileReader();

        reader.onload = event => {
          resolve(event.target.result);
        };

        if (this.resultType === "dataUrl") {
          reader.readAsDataURL(file);
        } else if (this.resultType === "text") {
          reader.readAsText(file);
        }
      });
    },

    onAfterRead(files, oversize) {
      if (oversize) {
        this.$emit("oversize", files);
      } else {
        this.afterRead && this.afterRead(files, this.index);
        this.$refs.input && (this.$refs.input.value = "");
      }
    },

    // click() {
    //   if (this.showNativeInput) return;
    //   this.$store.dispatch("wxChooseImage", 2).then(({ file, src }) => {
    //     this.onAfterRead(file, false);
    //   });
    // }
  }
};
</script>
<style scoped>
@import url("vant/lib/uploader");
input[type="file" i] {
  align-items: baseline;
  color: inherit;
  text-align: start !important;
}

.van-uploader {
  position: relative;
  display: inline-block;
}

.uploader {
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

.uploader.uploader-disabled {
  background: #f5f5f5;
}

.van-uploader__input {
  position: absolute;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}
</style>

