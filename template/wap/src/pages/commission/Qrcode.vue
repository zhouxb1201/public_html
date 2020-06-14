<template>
  <div class="commission-qrcode bg-f8">
    <Navbar :title="navbarTitle" />
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
    <van-cell-group class="cell-group">
      <van-field center readonly :value="shareUrl" label="邀请链接">
        <van-button
          class="a-copy"
          slot="button"
          size="mini"
          type="danger"
          :data-clipboard-text="shareUrl"
          @click="onCopy"
        >复制</van-button>
      </van-field>
      <van-field center readonly :value="$store.getters.extend_code" label="邀请码">
        <van-button
          class="a-copy"
          slot="button"
          size="mini"
          type="danger"
          :data-clipboard-text="$store.getters.extend_code"
          @click="onCopy"
        >复制</van-button>
      </van-field>
    </van-cell-group>
    <van-cell-group class="cell-group">
      <van-cell>
        <div>
          <p class="fs-12 text-regular">{{$store.state.member.commissionSetText.distribution_tips}}</p>
        </div>
      </van-cell>
    </van-cell-group>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { clipboard } from "@/mixins";
import PosterQr from "@/components/PosterQr";
export default sfc({
  name: "commission-qrcode",
  data() {
    return {
      imgUrl: "",
      showImgHtml: false
    };
  },
  mixins: [clipboard],
  computed: {
    navbarTitle() {
      const { extension_code } = this.$store.state.member.commissionSetText;
      let title = extension_code;
      document.title = title;
      return title;
    },
    size() {
      return document.body.offsetWidth / 2;
    },
    shareUrl() {
      let extend_code = this.$store.getters.extend_code;
      let url = `${this.$store.state.domain}/wap/`;
      if (extend_code) url = `${url}?extend_code=${extend_code}`;
      return url;
    }
  },
  mounted() {
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
    if (this.$store.state.config.addons.distribution == 1) {
      this.$store
        .dispatch("isBistributor")
        .then(() => {
          this.loadData();
        })
        .catch(({ error, callback }) => {
          if (error) {
            this.$refs.load.fail();
          }
          if (callback) {
            this.$refs.load.result();
            this.$Toast(
              "请先成为" +
                this.$store.state.member.commissionSetText.distributor_name +
                "！"
            );
            this.$router.replace("/member/centre");
          }
        });
    } else {
      this.$refs.load.fail({
        errorText: "未开启分销应用",
        showFoot: false
      });
    }
  },
  methods: {
    loadData() {
      const $this = this;
      $this.$store
        .dispatch("getPosterImg", {
          poster_type: 1
        })
        .then(imgUrl => {
          $this.imgUrl = imgUrl;
          $this.showImgHtml = false;
          $this.$refs.load.success();
        })
        .catch(() => {
          $this.showImgHtml = true;
          $this.$refs.load.success();
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
