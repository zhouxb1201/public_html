<template>
  <Layout ref="load" class="goods-share bg-f8">
    <Navbar :title="navBarTitle" />
    <div class="poster-box" v-if="imgUrl">
      <img class="poster-img" :src="imgUrl" />
    </div>
    <div class="head" ref="imageWrapper" v-show="!imgUrl">
      <div class="banner" :style="maxHeight">
        <img :src="goods_detail.image" :onerror="$ERRORPIC.noGoods" />
      </div>
      <div class="qr-box">
        <Qrcode class="img" :options="{ size: 120 }" :value="shareUrl" tag="img" />
        <div class="fs-12 text-regular">(长按识别)</div>
      </div>
    </div>
    <van-cell-group class="cell-group">
      <van-cell>
        <div class="title-share">
          <div class="title">{{ goods_detail.goods_name }}</div>
        </div>
        <div class="price">
          {{goodsPrice | yuan}}
          <!-- <van-tag plain type="danger" class="ml-10" v-if="discountText">{{discountText}}</van-tag> -->
          <small class="market">{{ goods_detail.market_price | yuan }}</small>
        </div>
      </van-cell>
    </van-cell-group>
    <van-cell class="cell">
      <div>分销小提示：</div>
      <div>
        <p>1、当用户通过你分享的链接或图片生成交易时，则该用户会成为你的客户，同时你也会获得相应佣金分成。</p>
        <p>2、佣金可在会员中心 --> 分销中心进行提现。</p>
      </div>
    </van-cell>

    <div class="foot van-hairline--top">
      <van-row type="flex" justify="space-around" class="box">
        <van-col span="12 van-hairline--right" @click.native="onToast('pic')">图片分享</van-col>
        <van-col span="12" @click.native="onToast('link')">链接分享</van-col>
      </van-row>
    </div>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { GET_GOODSSHAREINFO } from "@/api/goods";
import Qrcode from "@/components/Qrcode";
import { isEmpty, buildBase64ImageSrc } from "@/utils/util";
export default sfc({
  name: "goods-share",
  data() {
    return {
      imgUrl: "",
      goods_detail: {},
      buildImgLoading: ""
    };
  },
  computed: {
    navBarTitle() {
      const title = this.goods_detail.goods_name;
      if (title) document.title = title;
      return title;
    },
    maxHeight() {
      return {
        maxHeight: document.body.offsetWidth + "px"
      };
    },
    goodsPrice() {
      const member_discount = this.goods_detail.member_discount;
      const limit_discount = this.goods_detail.limit_discount;
      let discount_price = 0;
      let price = parseFloat(this.goods_detail.price);
      // discount_price = price * member_discount * limit_discount;
      discount_price = price * limit_discount;
      return discount_price.toFixed(2);
    },
    discountText() {
      const member_discount = this.goods_detail.member_discount;
      const limit_discount = this.goods_detail.limit_discount;
      let msg = false;
      if (member_discount !== 1 || limit_discount !== 1) {
        msg = "折扣价";
      }
      return msg;
    },
    shareUrl() {
      const $this = this;
      const symbol = isEmpty(this.$route.query) ? "?" : "&";
      let baseUrl = `${$this.$store.state.domain}/wap/goods/detail/${$this.goods_detail.goods_id}`;
      if ($this.channel_id) {
        baseUrl = `${baseUrl}?channel_id=${$this.channel_id}`;
      }
      let url = "";
      url = $this.$store.getters.extend_code
        ? `${baseUrl}${symbol}extend_code=${$this.$store.getters.extend_code}`
        : `${baseUrl}`;
      return url;
    },
    channel_id() {
      return this.$route.query.channel_id
        ? this.$route.query.channel_id
        : false;
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      GET_GOODSSHAREINFO($this.$route.params.goodsid)
        .then(({ data }) => {
          $this.goods_detail = data;
          $this
            .onShare({
              title: $this.goods_detail.goods_name,
              desc: `我刚刚在${$this.$store.getters.config.mall_name}发现了一个很不错的商品，赶快来看看吧。`,
              imgUrl: $this.$BASESRC($this.goods_detail.image),
              link: $this.shareUrl
            })
            .then(() => {
              buildBase64ImageSrc($this.goods_detail.image).then(src => {
                if (src) $this.goods_detail.image = src;
                $this.$refs.load.success();
                $this.$nextTick(() => {
                  $this.$store
                    .dispatch("buildImg", this.$refs.imageWrapper)
                    .then(imgUrl => {
                      $this.imgUrl = imgUrl;
                    });
                });
              });
            });
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    onToast(action) {
      const msg =
        action == "pic" ? "长按图片保存到手机分享" : "右上角分享或复制链接分享";
      this.$Toast(msg);
    }
  },
  components: {
    Qrcode
  }
});
</script>

<style scoped>
.goods-share {
  padding-bottom: 50px;
}
.poster-img {
  width: 100%;
  height: auto;
  display: block;
}
.head {
  position: relative;
}

.banner {
  width: 100%;
  overflow: hidden;
}

.banner img {
  width: 100%;
  height: auto;
  display: block;
}

.cell-group {
  margin: 10px 0;
}

.qr-box {
  position: absolute;
  right: 0;
  bottom: 0;
  z-index: 99;
  text-align: center;
  background: #ffffff;
  padding-bottom: 10px;
}

.qr-box .img {
  width: 120px;
  height: 120px;
  margin-bottom: 5px;
}

.qr-box .img img {
  width: 100%;
  height: 100%;
  display: block;
  background: #ffffff;
}

.title-share {
  display: flex;
  height: 48px;
}

.title-share .title {
  font-size: 16px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  flex: auto;
}

.price {
  color: #ff454e;
  font-size: 16px;
  font-weight: 800;
}

.price .market {
  font-weight: 400;
  color: #999;
  font-size: 12px;
  margin-left: 10px;
  text-decoration: line-through;
}

.ml-10 {
  margin-left: 10px;
}

.goods-share >>> .foot {
  width: 100%;
  position: fixed;
  bottom: 0;
  height: 44px;
  background: #fff;
}

.goods-share >>> .foot .box {
  align-items: center;
  height: 100%;
  text-align: center;
  line-height: 1.6;
}
</style>
