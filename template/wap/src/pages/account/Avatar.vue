<template>
  <Layout ref="load" class="account-avatar bg-f8">
    <Navbar :isMenu="false" />
    <div>
      <div class="box">
        <div class="img" :style="'maxHeight:'+imgHeight">
          <img :src="avatar" :onerror="$ERRORPIC.noAvatar" />
        </div>
        <div class="fixed-foot-btn-group">
          <UploadImages type="avatar" class="btn-upload" @finish="onUploadFinish">
            <van-button size="normal" type="danger" round block>上传头像</van-button>
          </UploadImages>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import UploadImages from "@/components/UploadImages";
import { isEmpty } from "@/utils/util";
export default sfc({
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
        return this.$store.state.account.info.avatar
          ? this.$store.state.account.info.avatar
          : "";
      },
      set(value) {
        console.log(value);
      }
    }
  },
  mounted() {
    if (isEmpty(this.$store.state.account.info)) {
      this.$store
        .dispatch("getAccountInfo")
        .then(() => {
          this.$refs.load.success();
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    } else {
      this.$refs.load.success();
    }
  },
  methods: {
    onUploadFinish({ src }) {
      this.$store.commit("setAvatar", src);
    }
  },
  components: {
    UploadImages
  }
});
</script>

<style scoped>
.box {
  display: flex;
  height: calc(100vh - 120px);
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

.btn-upload {
  width: 100%;
}
</style>
