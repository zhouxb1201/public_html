<template>
  <Layout ref="load" class="account-avatar bg-f8">
    <Navbar :isMenu="false"/>
    <div>
      <div class="box">
        <div class="img" :style="'maxHeight:'+imgHeight">
          <img :src="avatar" :onerror="$ERRORPIC.noAvatar">
        </div>
        <div class="fixed-foot-btn-group">
          <van-uploader :after-read="onRead" accept="image/png, image/jpeg" class="btn-upload">
            <van-button size="normal" type="danger" round block>上传头像</van-button>
          </van-uploader>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script>
import { Uploader } from "vant";
export default {
  name: "account-avatar",
  data() {
    return {};
  },
  computed: {
    imgHeight() {
      return document.body.offsetWidth - 30 + "px";
    },
    avatar: {
      get() {
        return this.$store.getters.avatar
          ? this.$store.getters.avatar
          : "";
      },
      set(value) {
        console.log(value);
      }
    }
  },
  created() {
    this.$store
      .dispatch("getAccountInfo")
      .then(() => {
        this.$refs.load.success();
      })
      .catch(() => {
        this.$refs.load.fail();
      });
  },
  methods: {
    onRead({ file }) {
      const $this = this;
      const maxSize = 1024;
      let fileSize = file.size / 1024;
      if (fileSize > maxSize) {
        $this.$Toast(`${file.name}图片大小不能超过1M`);
      } else {
        let param = new FormData();
        param.append("file", file);
        // param.append("type", "avatar");
        $this.$Toast.loading({
          message: "上传中...",
          duration: 0,
          forbidClick: true,
          loadingType: "circular"
        });
        $this.$store
          .dispatch("uploadImages", param)
          .then(res => {
            $this.$Toast.clear();
            $this.$Toast.success("上传成功！");
          })
          .catch(error => {
            $this.$Toast.clear();
            console.log(error);
            $this.$Toast.fail("上传失败！");
          });
      }
    }
  },
  components: {
    [Uploader.name]: Uploader
  }
};
</script>

<style scoped>
.box {
  display: flex;
  height: calc(100vh - 90px);
  align-items: center;
  justify-content: center;
}

.img {
  margin: 46px 15px 44px;
  overflow: hidden;
  max-width: 100%;
}

.img img {
  width: 100%;
  height: 100%;
  display: block;
}

.btn-box {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 99;
}
.btn-upload {
  width: 100%;
}
</style>
