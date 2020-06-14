<template>
  <Layout ref="load" class="integral-goods-detail">
    <Navbar :title="navBarTitle" />

    <div class="swipe-box" :style="maxHeight">
      <van-swipe class="swipe" :autoplay="3000" :style="maxHeight">
        <van-swipe-item v-for="(item,index) in goods_detail.goods_images" :key="index">
          <img :src="item" :style="maxHeight" :onerror="$ERRORPIC.noGoods" />
        </van-swipe-item>
      </van-swipe>
      <div class="box right" @click="showShare">
        <van-icon name="share" />
      </div>
    </div>

    <van-cell-group class="cell-group">
      <van-cell>
        <div class="title">{{ goods_detail.goods_name }}</div>
        <div class="price">
          <span v-if="goods_detail.point_exchange">{{goods_detail.point_exchange}}{{pointText}}</span>
          <span v-if="goods_detail.point_exchange && goodsPrice > 0">+</span>
          <span v-if="goodsPrice > 0">{{goodsPrice | yuan}}</span>
          <small class="market">{{ goods_detail.min_market_price | yuan }}</small>
        </div>
      </van-cell>

      <van-cell class="express">
        <van-col span="10">运费：{{ goods_detail.shipping_fee }}</van-col>
        <van-col span="14">兑换量：{{ goods_detail.sales }}</van-col>
      </van-cell>
    </van-cell-group>

    <van-cell v-if="goods_detail.day_num > 0 && goods_detail.limit_num > 0">
      <template slot="title">
        <van-tag type="primary">限购</van-tag>
        <span class="custom-text">每人限购{{goods_detail.limit_num}}件，每天提供{{goods_detail.day_num}}件</span>
      </template>
    </van-cell>

    <van-cell-group class="cell-group">
      <van-cell :title="sku_selectedText" is-link @click="onSku" />
    </van-cell-group>
    <van-sku
      v-model="showSku"
      :sku="goodsSku"
      :goods="goodsInfo"
      :goods-id="goods_detail.goods_id"
      :custom-stepper-config="customStepperConfig"
      :quota="quota"
      close-on-click-overlay
      @sku-selected="onSkuSelect"
      @buy-clicked="onBuyNow"
    >
      <div slot="sku-header-price" slot-scope="props">
        <div class="van-sku__goods-price">
          <span v-if="goodsPrice && goodsPoint">{{goodsPoint}} + {{goodsPrice | yuan }}</span>
          <span v-if="goodsPoint && !goodsPrice">{{goodsPoint}}</span>
        </div>
      </div>
      <div slot="sku-actions" slot-scope="props" v-if="goods_state === 1">
        <div class="van-sku-actions">
          <van-button type="primary" bottom-action @click="props.skuEventBus.$emit('sku:buy')">立即兑换</van-button>
        </div>
      </div>
    </van-sku>

    <van-cell-group class="cell-group">
      <van-tabs v-model="tab_active">
        <van-tab v-for="(tab,index) in tabs" :title="tab.name" :key="index">
          <component
            :is="'Detail'+tab.type"
            :descript="(tab.type == 'Descript' ? goods_detail.description : '')"
            :attribute="(tab.type == 'Attribute' ? (goods_detail.goods_attribute_list ? goods_detail.goods_attribute_list : []) : '')"
          />
        </van-tab>
      </van-tabs>
    </van-cell-group>

    <van-goods-action v-if="goods_state !== null" class="goods-action-group">
      <van-goods-action-mini-btn icon="wap-home" text="首页" to="/integral/index" />
      <van-goods-action-big-btn
        class="action-btn btn-buy"
        text="立即兑换"
        @click="onSku('buy')"
        v-if="goods_state === 1"
      />
      <van-goods-action-big-btn
        class="action-btn"
        disabled
        :text="goods_state_text"
        v-if="goods_state !== 1"
      />
    </van-goods-action>

    <!--分享-->
    <PopupShare :isShow="isShare" @click.native="closeShare"></PopupShare>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import {
  Swipe,
  SwipeItem,
  Sku,
  GoodsAction,
  GoodsActionBigBtn,
  GoodsActionMiniBtn
} from "vant";
import DetailDescript from "../component/detail/Descript";
import DetailAttribute from "../component/detail/Attribute";
import PopupShare from "../../wheelsurf/component/PopupShare";
import { GET_GOODSDETAIL } from "@/api/integral";
import { isEmpty } from "@/utils/util";
import { _encode } from "@/utils/base64";
import { bindMobile } from "@/mixins";
export default sfc({
  name: "integral-goods-detail",
  data() {
    return {
      tab_active: 0,
      tabs: [
        {
          name: "详情",
          type: "Descript"
        },
        {
          name: "属性",
          type: "Attribute"
        }
      ],
      goods_detail: {},

      sku_selectedComb: null,
      sku_selectedNum: 1,

      goodsSku: {},
      showSku: false,
      goodsInfo: {},

      isShare: false //是否弹出分享层
    };
  },
  mixins: [bindMobile],
  computed: {
    pointText() {
      return this.$store.state.member.memberSetText.point_style;
    },
    shareBtnStyle() {
      return {
        top: "15px",
        position: "absolute"
      };
    },
    goodsid() {
      return this.$route.params.goodsid;
    },
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
    // 商品状态 1==> 正常，-1==> 无库存，-2==> 积分不足，其他均不可操作
    goods_state() {
      let state = null;
      let point = this.sku_selectedComb
        ? this.sku_selectedComb.point_exchange
        : this.goods_detail.point_exchange;

      if (this.goods_detail && this.goods_detail.state) {
        if (this.countStock == 0) {
          state = -1;
        } else if (this.goods_detail.member_point < point) {
          state = -2;
        } else {
          state = this.goods_detail.state;
        }
      } else if (this.goods_detail.state == 0) {
        state = 0;
      }
      return state;
    },
    goods_state_text() {
      if (this.goods_state == -1) {
        return "商品已售罄";
      } else if (this.goods_state == -2) {
        return "积分不足";
      } else {
        return "商品已下架";
      }
    },
    // 显示商品价格(包含会员折扣和限时折扣)
    goodsPrice() {
      let price = parseFloat(
        this.sku_selectedComb
          ? this.sku_selectedComb.price / 100
          : this.goodsSku.price
      );
      return price;
    },
    //显示积分
    goodsPoint() {
      let point = this.sku_selectedComb
        ? this.sku_selectedComb.point_exchange
        : this.goods_detail.point_exchange;
      return point + this.pointText;
    },
    // 转换sku列表数据 ( 元 => 分 )
    tranList() {
      const list = this.goods_detail.sku.list;
      list.forEach(e => {
        e.price = parseFloat(e.price) * 100;
      });
      return list;
    },
    // 计算总库存
    countStock() {
      const list = this.goods_detail.sku ? this.goods_detail.sku.list : [];
      let stock = 0;
      if (list && !isEmpty(list)) {
        list.forEach(e => {
          stock += parseInt(e.stock_num);
        });
      }
      return stock;
    },
    sku_selectedText() {
      const $this = this;
      let text = "";
      if ($this.goodsSku.none_sku) {
        text = "已选：" + $this.sku_selectedNum;
      } else {
        if ($this.sku_selectedComb) {
          text = "已选：" + $this.sku_selectedComb.sku_name;
        } else {
          text = "请选择规格";
        }
      }
      return text;
    },
    // sku限购数量
    quota() {
      const $this = this;
      const goodsSku = isEmpty($this.goodsSku) ? null : $this.goodsSku;
      const sku_selectedComb = $this.sku_selectedComb;
      let quota = -1;
      if (goodsSku) {
        if (!goodsSku.none_sku && sku_selectedComb) {
          // console.log("有规格商品并已选规格");
          quota = sku_selectedComb.max_buy;
        } else if (goodsSku.none_sku) {
          // console.log("无规格商品");
          quota = goodsSku.list[0].max_buy;
        }
      }
      return quota;
    },
    // sku 步进器操作
    customStepperConfig() {
      const $this = this;
      const quotaText = $this.quota == -1 ? "" : "限购" + $this.quota + "件";
      return {
        handleOverLimit: data => {
          const { action, limitType, quota, quotaUsed } = data;
          if (action === "minus") {
            $this.$Toast("至少选择一件！");
          } else if (action === "plus") {
            $this.$Toast(limitType === 1 ? "库存不足！" : `限购${quota}件！`);
          }
        },
        handleStepperChange: num => {
          // 限制最大输入数量
          const limitFlag = $this.quota > 0; // 活动限购量 真为限购 否为不限购
          let stock = 0; // 库存量
          if (!$this.goodsSku.none_sku && $this.sku_selectedComb) {
            stock = $this.sku_selectedComb.stock_num;
          } else {
            stock = $this.countStock;
          }
          let limitNum = $this.quota > stock ? stock : $this.quota; //限购量不能大于库存
          $this.sku_selectedNum = limitFlag
            ? num > limitNum
              ? limitNum
              : num
            : num > stock
            ? stock
            : num;
        }
      };
    }
  },
  mounted() {
    this.$refs.load.success();
    this.loadData();
  },
  methods: {
    loadData() {
      const $this = this;
      const params = {};
      params.goods_id = $this.goodsid;
      GET_GOODSDETAIL(params).then(({ data }) => {
        const goods = data.goods_detail;
        $this.goods_detail = goods;

        $this.goodsSku = {
          tree: goods.sku.tree ? goods.sku.tree : [],
          list: $this.tranList,
          price: goods.min_price,
          stock_num: $this.countStock, // 商品总库存
          collection_id: isEmpty(goods.sku.tree) ? $this.tranList[0].id : 0, // 无规格商品 skuId 取 collection_id，否则取所选 sku 组合对应的 id
          none_sku: isEmpty(goods.sku.tree) // 是否无规格商品
        };

        $this.goodsInfo = {
          title: goods.goods_name,
          picture: $this.$BASESRC(goods.goods_images[0])
        };
      });
    },
    onSku(type) {
      const $this = this;
      $this.showSku = true;
    },
    // 选择规格
    onSkuSelect(data) {
      this.sku_selectedComb = data.selectedSkuComb;
    },
    onBuyNow(data) {
      const $this = this;
      $this.bindMobile().then(() => {
        const params = {};
        params.sku_list = [];
        let sku_list_obj = {};
        sku_list_obj.num =
          $this.sku_selectedNum > 0 ? $this.sku_selectedNum : 1;
        sku_list_obj.sku_id = data.selectedSkuComb.id;
        params.sku_list.push(sku_list_obj);
        $this.$router.push({
          name: "integral-order-confirm",
          query: {
            params: _encode(JSON.stringify(params))
          }
        });
      });
    },
    showShare() {
      if (this.$store.state.isWeixin) {
        this.isShare = true;
      } else {
        this.$Toast("请点击下方工具栏“分享”按钮进行分享");
      }
    },
    //关闭分享
    closeShare() {
      this.isShare = false;
    }
  },
  components: {
    DetailDescript,
    DetailAttribute,
    PopupShare,
    [Swipe.name]: Swipe,
    [SwipeItem.name]: SwipeItem,
    [Sku.name]: Sku,
    [GoodsAction.name]: GoodsAction,
    [GoodsActionBigBtn.name]: GoodsActionBigBtn,
    [GoodsActionMiniBtn.name]: GoodsActionMiniBtn
  }
});
</script>

<style scoped>
.integral-goods-detail {
  padding-bottom: 50px;
}
.swipe-box {
  position: relative;
  overflow: hidden;
}
.swipe img {
  width: 100%;
  height: auto;
  display: block;
}

.title {
  font-size: 16px;
  height: 48px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  flex: auto;
  color: #333;
}
.cell-group {
  margin: 10px 0;
}
.cell-group .van-cell__value {
  color: #999;
}

.express {
  color: #999;
  font-size: 12px;
  padding: 5px 15px;
}

.price {
  color: #ff454e;
  font-size: 16px;
  font-weight: 800;
  height: 26px;
}

.price .market {
  font-weight: 400;
  color: #999;
  font-size: 12px;
  margin-left: 10px;
  text-decoration: line-through;
}
.btn-buy {
  background: #ff454e;
  border: 1px solid #ff454e;
}

.van-goods-action {
  z-index: 999;
}
.goods-action-group {
  background: #fff;
}

.goods-action-group .action-btn {
  font-size: 14px;
  height: 40px;
  line-height: 38px;
  margin: 5px;
  border-radius: 8px;
}
.box {
  border-radius: 100%;
  background: rgba(0, 0, 0, 0.3);
  width: 30px;
  height: 30px;
  position: absolute;
  z-index: 100;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  left: 15px;
  top: 15px;
  font-weight: 800;
}
.box.right {
  left: initial;
  right: 15px;
}
.box:active {
  background: rgba(0, 0, 0, 0.6);
}
</style>
