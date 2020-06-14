<template>
  <div class="share-box">
    <HeadBtn v-if="$store.state.isWeixin" :style="shareBtnStyle" />
    <HeadBtn
      v-if="$store.state.message.showService"
      :style="shareBtnStyle"
      class="cart-btn"
      event
      @click="$router.push('/mall/cart')"
      dir="right"
      icon="v-icon-cart1"
    />
    <HeadBtn
      type="share"
      event
      :style="shareBtnStyle"
      v-if="!showLoading"
      @click="onClick"
    />
    <div class="loading-box" :style="shareBtnStyle" v-else>
      <van-loading color="white" size="20px" />
    </div>
    <div
      class="image-wrapper"
      ref="imageWrapper"
      v-if="!imgUrl && showImgHtml && shareUrl"
    >
      <div class="img-box">
        <div class="banner">
          <img :src="goodsImg" id="goodsImg" :onerror="$ERRORPIC.noGoods" />
        </div>
        <van-cell class="info-box">
          <div class="title">{{ info.title }}</div>
          <div class="price">
            {{ info.price | yuan }}
            <small class="market">{{ info.marketPrice | yuan }}</small>
          </div>
        </van-cell>
        <div class="qr-box">
          <Qrcode
            class="img"
            :options="{ size: 80 }"
            :value="shareUrl"
            tag="img"
            id="qrImg"
          />
        </div>
      </div>
    </div>
    <van-popup v-model="popupShow" class="share-popup">
      <div class="poster-box">
        <img class="poster-img" :src="imgUrl" />
      </div>
      <div class="foot">
        <small class="tip">
          <span>{{
            $store.state.isWeixin
              ? "长按图片可保存图片或分享"
              : "长按图片可保存图片"
          }}</span>
          <span>{{
            $store.state.isWeixin
              ? "如需链接分享，请点击右上角进行分享"
              : "如需链接分享，请点击下方进行分享"
          }}</span>
        </small>
      </div>
    </van-popup>
  </div>
</template>

<script>
import HeadBtn from "@/components/HeadBtn";
import Qrcode from "@/components/Qrcode";
import { isEmpty, filterUriParams } from "@/utils/util";
import { Loading } from "vant";
import { GET_GOODSIMGBASE64 } from "@/api/goods";
export default {
  data() {
    return {
      popupShow: false,
      showImgHtml: false,
      imgUrl: "",
      shareUrl: "",
      showLoading: false,
      goodsImg: ""
    };
  },
  props: {
    info: Object
  },
  computed: {
    shareBtnStyle() {
      return {
        top: "15px",
        position: "absolute"
      };
    }
  },
  watch: {
    "info.id"(e) {
      if (e) {
        this.clickEvent = false;
        this.imgLoading = "";
        this.showLoading = false;
        this.imgUrl = "";
      }
    },
    imgUrl(e) {
      this.onShow();
    }
  },
  methods: {
    getShareUrl() {
      return new Promise((resolve, reject) => {
        const $this = this;
        const baseUrl = `${$this.$store.state.domain}/wap`;
        const isQuery = filterUriParams(this.$route.query, "extend_code");
        const fullPath = this.$route.path + isQuery;
        let url = `${baseUrl}${fullPath}`;
        $this.$store.dispatch("getExtendCode").then(extend_code => {
          if (extend_code) {
            url = isQuery
              ? `${url}&extend_code=${extend_code}`
              : `${url}?extend_code=${extend_code}`;
          }
          $this.shareUrl = url;
          resolve();
        });
      });
    },
    getGoodsImgBase64(goods_id) {
      return new Promise((resolve, reject) => {
        GET_GOODSIMGBASE64(goods_id)
          .then(({ data }) => {
            resolve(data[0] || this.info.picture);
          })
          .catch(() => {
            resolve(this.info.picture);
          });
      });
    },
    getImgUrl() {
      const $this = this;
      $this.$store
        .dispatch("getPosterImg", {
          poster_type: 2,
          goods_id: $this.info.id
        })
        .then(imgUrl => {
          $this.showImgHtml = false;
          $this.imgUrl = imgUrl;
        })
        .catch(() => {
          $this.getGoodsImgBase64($this.info.id).then(img => {
            $this.goodsImg = img;
            $this.showImgHtml = true;
            $this.getShareUrl().then(url => {
              Promise.all([$this.loadedGoodsImg(), $this.loadedQrImg()])
                .then(() => {
                  $this.$nextTick(() => {
                    $this.$store
                      .dispatch("buildImg", $this.$refs.imageWrapper)
                      .then(imgUrl => {
                        $this.imgUrl = imgUrl;
                      });
                  });
                })
                .catch(() => {
                  $this.$nextTick(() => {
                    $this.$store
                      .dispatch("buildImg", $this.$refs.imageWrapper)
                      .then(imgUrl => {
                        $this.imgUrl = imgUrl;
                      });
                  });
                });
            });
          });
        });
    },
    onClick() {
      this.clickEvent = true;
      if (this.imgUrl) {
        this.onShow();
      } else {
        this.getImgUrl();
        this.showLoading = true;
        this.imgLoading = this.$Toast.loading({
          message: "海报生成中",
          duration: 0,
          loadingType: "spinner"
        });
      }
    },
    onShow() {
      if (this.imgLoading) {
        this.imgLoading.clear();
      }
      if (this.clickEvent) {
        this.popupShow = true;
        this.showLoading = false;
      }
    },
    loadedGoodsImg() {
      return new Promise((reslove, reject) => {
        document.getElementById("goodsImg").onload = function(e) {
          reslove(e);
        };
      });
    },
    loadedQrImg() {
      return new Promise((reslove, reject) => {
        document.getElementById("qrImg").onload = function(e) {
          reslove(e);
        };
      });
    }
  },
  beforeDestroy() {
    if (this.imgLoading) {
      this.imgLoading.clear();
    }
  },
  components: {
    HeadBtn,
    Qrcode,
    [Loading.name]: Loading
  }
};
</script>

<style scoped>
.loading-box {
  border-radius: 100%;
  background: rgba(0, 0, 0, 0.3);
  width: 30px;
  height: 30px;
  position: fixed;
  z-index: 100;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  right: 15px;
  top: 15px;
  font-weight: 800;
}

.share-box {
  position: relative;
}

.share-popup {
  width: 80%;
  max-height: 90%;
  background: transparent;
  border-radius: 10px;
}

.image-wrapper {
  -webkit-touch-callout: none;
  -webkit-user-select: none;
  -khtml-user-select: none;
  -moz-user-select: none;
  -ms-user-select: none;
  user-select: none;
  position: absolute;
  top: -100%;
  z-index: -1;
  width: 80%;
}

.poster-img {
  width: 100%;
  height: auto;
  display: block;
}

.img-box {
  position: relative;
}

.banner {
  height: 0;
  width: 100%;
  padding: 50% 0;
  overflow: hidden;
  background: #f9f9f9;
}

.banner img {
  display: block;
  width: 100%;
  margin-top: -50%;
  background-color: #fff;
  border: none;
}

.cell-group {
  margin: 10px 0;
}

.info-box {
  padding-right: 84px;
}

.qr-box {
  position: absolute;
  right: 0;
  bottom: 5px;
  z-index: 99;
  text-align: center;
  background: #ffffff;
}

.qr-box .img {
  width: 80px;
  height: 80px;
  display: block;
}

.qr-box .img img {
  width: 100%;
  height: 100%;
  display: block;
  background: #ffffff;
}

.title {
  overflow: hidden;
  height: 40px;
  line-height: 20px;
  margin-bottom: 4px;
  font-size: 12px;
}

.price {
  color: #ff454e;
  font-size: 16px;
  font-weight: 800;
  white-space: nowrap;
}

.price .market {
  font-weight: 400;
  color: #999;
  font-size: 12px;
  margin-left: 10px;
  text-decoration: line-through;
}

.foot {
  padding: 10px;
  background: #fff;
}

.foot .tip {
  display: flex;
  flex-direction: column;
  text-align: center;
  font-size: 10px;
  color: #909399;
  line-height: 1.4;
}

.share-box .box.cart-btn {
  font-size: 14px;
  right: 55px;
}
</style>
