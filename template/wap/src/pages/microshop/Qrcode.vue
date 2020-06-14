<template>
  <div class="microshop-qrcode bg-f8">
    <Navbar />
    <van-cell-group>
      <Layout ref="load" class="poster-wrp" :style="{minHeight:size*2+'px'}" loadingText="海报生成中">
        <div v-if="imgUrl">
          <img :src="imgUrl" />
        </div>
        <PosterQr
          ref="imageWrapper"
          :qr-url="shareUrl"
          v-if="!imgUrl && showImgHtml"
          :loaded="onLoaded"
        />
      </Layout>
      <van-cell>
        <div class="text-center fs-12 text-regular">(长按保存海报分享)</div>
      </van-cell>
    </van-cell-group>
    <van-cell class="cell-group">
      <div>微店小提示：</div>
      <div>
        <p>可以通过二维码或微店链接邀请朋友购买微店商品，成交后你将会获得相应比例的返利。</p>
      </div>
    </van-cell>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import PosterQr from "@/components/PosterQr";
export default sfc({
  name: "microshop-qrcode",
  data() {
    return {
      imgUrl: "",
      showImgHtml: false
    };
  },
  computed: {
    info() {
      return this.$store.state.microshop.info;
    },
    shopkeeper_id() {
      const { info } = this;
      return info && info.uid ? info.uid : "";
    },
    size() {
      return document.body.offsetWidth / 2;
    },
    shareUrl() {
      let extend_code = this.$store.getters.extend_code;
      let url = `${this.$store.state.domain}/wap/microshop/previewshop?shopkeeper_id=${this.shopkeeper_id}`;
      if (extend_code) url = `${url}&extend_code=${extend_code}`;
      return url;
    }
  },
  mounted() {
    if (this.$store.state.config.addons.microshop == 1) {
      this.$store
        .dispatch("getMicroshopInfo")
        .then(() => {
          if (this.$store.state.microshop.info.isshopkeeper) {
            this.loadData();
          } else {
            this.$refs.load.result();
            this.$Toast("请先成为微店店主！");
            this.$router.replace("/member/centre");
          }
        })
        .catch(error => {
          if (error) {
            this.$refs.load.fail();
          } else {
            this.$refs.load.result();
          }
        });
    } else {
      this.$refs.load.fail({
        errorText: "未开启微店应用",
        showFoot: false
      });
    }
  },
  methods: {
    loadData() {
      this.$store
        .dispatch("getPosterImg", {
          poster_type: 4
        })
        .then(imgUrl => {
          this.imgUrl = imgUrl;
          this.showImgHtml = false;
          this.$refs.load.success();
        })
        .catch(() => {
          this.showImgHtml = true;
          this.$refs.load.success();
        });
    },
    onLoaded() {
      this.$nextTick(() => {
        this.$store
          .dispatch("buildImg", this.$refs.imageWrapper.$el)
          .then(imgUrl => {
            this.imgUrl = imgUrl;
          });
      });
    }
  },
  components: {
    PosterQr
  }
});
</script>

<style scoped>
.cell-group {
  margin: 10px 0;
}

.poster-wrp img {
  width: 100%;
  display: block;
}
</style>
