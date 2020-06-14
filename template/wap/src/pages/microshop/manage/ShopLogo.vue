<template>
  <Layout ref="load" class="microshop-shoplogo bg-f8">
    <Navbar :isMenu="false" />
    <div>
      <div class="box">
        <div class="img" :style="'maxHeight:'+imgHeight">
          <img :src="mic_logo" :onerror="$ERRORPIC.noAvatar" />
        </div>
        <div class="fixed-foot-btn-group">
          <UploadImages class="btn-upload" @finish="onUploadFinish">
            <van-button size="normal" type="danger" round block>上传微店Logo</van-button>
          </UploadImages>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { getSession } from "@/utils/storage";
import { GET_SHOPSET } from "@/api/microshop";
import UploadImages from "@/components/UploadImages";
export default sfc({
  name: "microshop-shoplogo",
  data() {
    return {
      mic_logo: null
    };
  },
  computed: {
    imgHeight() {
      return document.body.offsetWidth - 30 + "px";
    },
    microshop_name() {
      return getSession("set").microshop_name
        ? getSession("set").microshop_name
        : "";
    },
    microshop_introduce() {
      return getSession("set").microshop_introduce
        ? getSession("set").microshop_introduce
        : "";
    },
    microshop_logo() {
      return getSession("set").microshop_logo
        ? getSession("set").microshop_logo
        : "";
    },
    shopRecruitment_logo() {
      return getSession("set").shopRecruitment_logo
        ? getSession("set").shopRecruitment_logo
        : "";
    }
  },
  mounted() {
    this.mic_logo = this.microshop_logo;
    this.$refs.load.success();
  },
  methods: {
    onUploadFinish({ src }){
      const $this = this;
      let params = {
        shopRecruitment_logo: $this.shopRecruitment_logo,
        microshop_name: $this.microshop_name,
        microshop_introduce: $this.microshop_introduce
      };
      params.microshop_logo = src.toString();
      $this.mic_logo = src.toString();
      GET_SHOPSET(params).then(data => {
        $this.$store.dispatch("getMicroshopInfo").then();
      });
      setTimeout(() => {
        $this.$router.go(-1);
      }, 500);
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
