<template>
  <Layout ref="load" class="goods-detail bg-f8" :style="pageParam.style">
    <Navbar :title="pageParam.title" />
    <InviteWechat />
    <ShareBtn :info="posterInfo" />
    <CustomGroup type="3" :items="items" @event="onEvent" />
    <SkuPopup
      v-model="showSku"
      :info="goodsData"
      :action="skuAction"
      :promote-type="promoteType"
      :promote-params="promoteParams"
      :get-goods-info="getGoodsInfo"
      @action="onSkuAction"
      @close="onSkuClose"
    />
    <BottomGoodsAction
      :goods-info="goodsInfo"
      :promote-type="promoteType"
      :promote-params="promoteParams"
      @action="onShowSku"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import InviteWechat from "@/components/InviteWechat";
import CustomGroup from "@/components/CustomGroup";
import SkuPopup from "@/components/sku-popup/Sku";
import ShareBtn from "./component/detail/ShareBtn";
import BottomGoodsAction from "./component/detail/BottomGoodsAction";

import { GET_GOODSDETAIL, ADD_CART, GET_GOODSINFO } from "@/api/goods";
import { isEmpty, filterUriParams } from "@/utils/util";
import { setSession } from "@/utils/storage";
import { _encode } from "@/utils/base64";
import { bindMobile } from "@/mixins";

import { defaultData } from "./default-custom-data";

let addCartFlag = true; // 防止重复点击添加多次购物车

export default sfc({
  name: "goods-detail",
  data() {
    return {
      pageParam: {
        style: null,
        title: null
      },
      items: {},

      /**
       * 活动类型
       * normal    =>    普通商品类型(默认)
       * seckill   =>    秒杀商品类型
       * group     =>    拼团商品类型
       * presell   =>    预售商品类型
       * bargain   =>    砍价商品类型
       * limit     =>    限时商品类型
       */
      promoteType: "normal",
      promoteParams: {},
      goodsData: null,
      showSku: false,
      goodsInfo: {},
      skuAction: "", //sku触发行动类型，指触发哪个活动类型

      current_record_id: "", //用户选中参加的团id
      deliveryInfo: {
        type: "express"
      },

      // 海报信息
      posterInfo: {
        id: 0,
        picture: "",
        title: "",
        price: 0,
        marketPrice: 0
      }
    };
  },
  watch: {
    "$route.params.goodsid"(id) {
      if (id && this.$route.name == "goods-detail") {
        this.$refs.load.init();
        this.loadData();
      }
    }
  },
  computed: {
    params() {
      const { record_id, channel_id } = this.$route.query;
      let params = {
        goods_id: this.$route.params.goodsid
      };
      if (record_id) {
        params.record_id = record_id;
      }
      if (channel_id) {
        params.channel_id = channel_id;
      }
      return params;
    }
  },
  mixins: [bindMobile],
  mounted() {
    this.loadData();
  },
  methods: {
    getGoodsInfo(data) {
      this.goodsInfo = data;
      for (let key in this.items) {
        let item = this.items[key];
        if (item.id === "detail_info") {
          item.goodsInfo = data;
        }
        if (item.id === "detail_specs") {
          item.params.isCurrent = !!data.selectedSkuComb;
          if (data.isSpec) {
            item.params.valueText = data.selectedSkuComb
              ? data.selectedSkuComb.sku_name
              : "请选择规格";
          } else {
            item.params.valueText = "已选：" + data.selectedNum + "件";
          }
        }
      }
    },
    onSkuClose(action) {
      if (action == "group" && this.current_record_id) {
        // 拼团活动，关闭sku时，清除用户参与指定团的id
        this.current_record_id = "";
      }
    },
    onShowSku(action) {
      if (action == "study") {
        this.onStudy({ goods_id: this.params.goods_id });
      } else {
        this.showSku = true;
        this.skuAction = action;
      }
    },
    // sku触发事件
    onSkuAction(action, data) {
      if (
        action === "buy" ||
        action === "group" ||
        action === "seckill" ||
        action === "presell"
      ) {
        this.onBuyNow(data);
      } else if (action === "addCart") {
        this.onAddCart(data);
      } else if (action == "bargain") {
        this.onBargain(data);
      } else if (action == "study") {
        this.onStudy(data);
      } else {
        this.$Toast("暂无后续操作" + action);
      }
    },
    onAddCart(data) {
      this.bindMobile().then(() => {
        if (!addCartFlag) return;
        addCartFlag = false;
        let params = {};
        params.num = data.selectedNum;
        params.sku_id = data.selectedSkuComb.id;
        if (this.$route.query.shopkeeper_id) {
          //微店id
          params.shopkeeper_id = this.$route.query.shopkeeper_id;
          setSession("shopkeeper_id", this.$route.query.shopkeeper_id);
        }
        if (data.seckill_id) {
          //秒杀商品加入购物车
          params.seckill_id = data.seckill_id;
        }
        ADD_CART(params)
          .then(({ message }) => {
            this.$Toast.success(message);
            if (this.showSku) {
              this.showSku = false;
              setTimeout(() => {
                addCartFlag = true;
              }, 200);
            }
          })
          .catch(() => {
            addCartFlag = true;
          });
      });
    },
    onBuyNow(data) {
      this.bindMobile().then(() => {
        let params = {};
        params.order_tag = "buy_now";
        params.goodsType = data.goodsType;
        if (this.deliveryInfo && this.deliveryInfo.type == "express") {
          params.address_id = this.deliveryInfo.id;
        }
        if (this.$route.query.shopkeeper_id) {
          //微店id
          params.shopkeeper_id = this.$route.query.shopkeeper_id;
          setSession("shopkeeper_id", this.$route.query.shopkeeper_id);
        }
        if (data.presell_id) {
          params.presell_id = data.presell_id;
        }
        if (data.group_id) {
          params.group_id = data.group_id;
          if (this.current_record_id) {
            //表示用户自己选中参加哪个团
            params.record_id = this.current_record_id;
          } else if (data.record_id) {
            //存在record_id表示已经指定参数哪个团
            params.record_id = data.record_id;
          }
        }
        params.sku_list = [];
        let sku_list_obj = {};
        sku_list_obj.num = data.selectedNum;
        sku_list_obj.sku_id = data.selectedSkuComb.id;
        sku_list_obj.shop_id = data.shopId;
        if (this.deliveryInfo && this.deliveryInfo.type == "pickup") {
          sku_list_obj.store_id = this.deliveryInfo.id;
          sku_list_obj.store_name = this.deliveryInfo.name;
        }
        if (data.seckill_id) {
          sku_list_obj.seckill_id = data.seckill_id;
        }
        if (this.params.channel_id) {
          sku_list_obj.channel_id = this.params.channel_id;
        }
        params.sku_list.push(sku_list_obj);
        // return console.log(params);
        this.$router.push({
          name: "order-confirm",
          query: {
            params: _encode(JSON.stringify(params))
          }
        });
      });
    },
    // 砍价
    onBargain(data) {
      this.$router.push({
        name: "bargain-detail",
        params: {
          goodsid: data.id,
          bargainid: data.bargain_id,
          bargainuid: data.bargain_uid
        }
      });
    },
    // 学习课程
    onStudy(data) {
      this.$router.push({
        name: "course-detail",
        params: {
          id: data.goods_id
        }
      });
    },
    /**
     * 监听组件所触发回调方法
     * event  事件类型
     * data  事件携带参数数据
     */
    onEvent(event, data) {
      if (event === "specs") {
        this.skuAction = "";
        this.showSku = true;
      } else if (event === "group") {
        // 获取参加指定团 团id
        this.current_record_id = data;
        this.skuAction = "group";
        this.showSku = true;
      } else if (event === "delivery") {
        if (this.deliveryInfo.type != data.type || data.type == "pickup") {
          this.getStoreGoodsDetail(data.type == "pickup" && data.id);
        }
        this.deliveryInfo = data;
      } else if (event === "initData") {
        // 初始数据
        this.loadData();
      }
    },
    loadData() {
      GET_GOODSDETAIL(this.params)
        .then(({ data }) => {
          if (data.is_allow_browse === false) {
            return this.$refs.load.fail({
              errorType: "goods",
              errorText: "您自身等级无权限浏览该商品",
              showFoot: false
            });
          }
          this.detailData = data;
          this.goodsData = this.packageGoodsData(data);
          this.promoteType = this.packagePromoteData(data).type;
          this.promoteParams = this.packagePromoteData(data).info;
          this.posterInfo = {
            id: data.goods_detail.goods_id,
            picture: data.goods_detail.goods_images[0],
            title: data.goods_detail.goods_name,
            price: data.goods_detail.poster_price || 0,
            marketPrice: data.goods_detail.min_market_price
          };
          this.onShare({
            title: data.goods_detail.goods_name,
            desc: `我刚刚在${this.$store.getters.config.mall_name}发现了一个很不错的商品，赶快来看看吧。`,
            imgUrl: data.goods_detail.goods_images[0],
            link:
              this.$store.state.domain +
              "/wap" +
              this.$route.path +
              filterUriParams(this.$route.query, "extend_code")
          });
          this.$store
            .dispatch("getCustom", {
              type: 3,
              shop_id: data.goods_detail.shop_id
            })
            .then(({ template_data }) => {
              let customData = this.initCustomData(template_data.items);
              this.items = this.packageItemsData(customData, data);
              this.$refs.load.success();
              this.setPageParam({
                title: data.goods_detail.goods_name,
                background: template_data.page.background
              });
            })
            .catch(() => {
              this.$refs.load.fail();
            });
        })
        .catch(() => {
          this.$refs.load.fail();
        });
    },
    // 兼容旧数据
    initCustomData(data = {}) {
      let items = {};
      const templateItems = defaultData.items;
      for (const key in data) {
        let item = data[key];
        if (item.id == "detail_fixed") {
          //存在detail_fixed组件为旧数据，删除该组件，重新组装新组件数据
          delete data[key];
          for (const tKey in templateItems) {
            items[tKey] = templateItems[tKey];
          }
        } else {
          items[key] = item;
        }
      }
      return items;
    },
    // 设置页码参数
    setPageParam({ title, background }) {
      this.pageParam.title = title;
      if (title) document.title = title;
      this.pageParam.style = {
        background
      };
    },
    // 组装装修数据所需数据
    packageItemsData(items, detail) {
      let data = Object.assign({}, items);
      for (let key in data) {
        let item = data[key];
        if (item.id === "detail_banner") {
          item.data = [];
          item.data = detail.goods_detail.goods_images;
          item.params = {};
          item.params.video = detail.goods_detail.video;
        }
        if (item.id === "detail_info") {
          item.params = {};
          item.params.sales = detail.goods_detail.sales || 0;
          item.params.postage = detail.goods_detail.shipping_fee;
          item.goodsInfo = {};
          item.params.promoteType = this.promoteType;
          item.params.promoteParams = this.promoteParams;
          item.params.digitalAsset = {
            eth: detail.eth_market,
            eos: detail.eos_market,
            fee: detail.service_charge
          };
        }
        if (item.id === "detail_specs") {
          item.params = {};
          item.params.valueText = "";
          item.params.isCurrent = false;
          item.params.show = detail.goods_detail.goods_type != 4;
        }
        if (item.id === "detail_shop") {
          item.params = {};
          item.params.id = detail.goods_detail.shop_id;
          item.params.logo = detail.shop_type_info.shop_logo;
          item.params.name = detail.shop_type_info.shop_name;
          item.params.delivery = detail.shop_type_info.shop_deliverycredit;
          item.params.desc = detail.shop_type_info.shop_desccredit;
          item.params.service = detail.shop_type_info.shop_servicecredit;
          item.params.comprehensive = detail.shop_type_info.comprehensive;
        }
        if (item.id === "detail_desc") {
          item.params = {};
          item.params.tabs = [];
          let descript = {
            text: "描述",
            name: "descript",
            data: detail.goods_detail.description
          };
          let attribute = {
            text: "属性",
            name: "attribute",
            data: detail.goods_detail.goods_attribute_list || []
          };
          let catalog = {
            text: "目录",
            name: "catalog",
            data: detail.goods_detail.goods_id
          };
          let evaluate = {
            text: "评论",
            name: "evaluate",
            data: detail.goods_detail.goods_id
          };
          item.params.tabs.push(descript);
          if (detail.goods_detail.goods_type == 4) {
            item.params.tabs.push(catalog);
          } else {
            item.params.tabs.push(attribute);
          }
          item.params.tabs.push(evaluate);
        }
        if (item.id === "detail_promote") {
          item.data = item.data || {};
          item.params = {};
          for (const pKey in item.data) {
            let pItem = item.data[pKey];
            pItem.show = true;
            if (pItem.key == "fullcut") {
              pItem.data = detail.full_cut_list;
            } else if (pItem.key == "coupon") {
              pItem.data = detail.coupon_type_list || [];
              pItem.data.forEach(e => {
                e.loading = false;
                e.title = "";
              });
            } else if (pItem.key == "rebate") {
              pItem.data = { ...detail.distributor_res, ...detail.give_point };
            }
          }
          if (this.promoteType == "presell") {
            item.params = this.promoteParams;
            item.params.promoteType = this.promoteType;
          }
          if (
            this.promoteType == "group" &&
            !this.params.record_id &&
            this.promoteParams.group_record_count
          ) {
            item.params = this.promoteParams;
            item.params.promoteType = this.promoteType;
          }
        }
        if (item.id === "detail_delivery") {
          item.params = {};
          item.params.isCurrent = !!this.deliveryInfo.id;
          item.params.show = detail.goods_detail.goods_type != 4;
          item.params.info = {};
          item.params.info.address =
            this.deliveryInfo && this.deliveryInfo.address;
          item.params.has_express = detail.has_express;
          item.params.has_store = detail.has_store;
        }
        if (item.id === "detail_service") {
          item.params = {};
          item.params.show = detail.goods_detail.goods_type != 4;
        }
      }
      return data;
    },
    // 组装商品所需数据
    packageGoodsData(data) {
      let info = {};
      // 组装sku所需数据格式
      info = data.goods_detail;
      info.goods_image = data.goods_detail.goods_image_yun;
      info.is_allow_buy =
        typeof data.is_allow_buy == "boolean" ? data.is_allow_buy : true;
      return info;
    },
    // 组装活动商品所需数据
    packagePromoteData(data) {
      let type = "normal";
      let info = {};
      if (data.seckill_list.seckill_id) {
        type = "seckill";
      } else if (data.group_list.group_id) {
        type = "group";
      } else if (data.presell_list.presell_id) {
        type = "presell";
      } else if (data.bargain_list.bargain_id) {
        type = "bargain";
      } else if (data.limit_list && data.limit_list.discount_id) {
        type = "limit";
      }
      if (type != "normal") {
        info = data[`${type}_list`] || {};
      }
      return {
        type,
        info
      };
    },
    getStoreGoodsDetail(storeId) {
      let params = Object.assign({}, this.params);
      storeId && (params.store_id = storeId);
      GET_GOODSINFO(params, { loading: true }).then(({ data }) => {
        let goodsData = this.goodsData;
        let storeGoodsData = data.goods_detail;
        for (const key in storeGoodsData) {
          if (goodsData.hasOwnProperty(key)) {
            goodsData[key] = storeGoodsData[key];
          }
        }
        this.$store
          .dispatch("getCustom", {
            type: 3,
            shop_id: data.goods_detail.shop_id
          })
          .then(({ template_data }) => {
            let customData = this.initCustomData(template_data.items);
            this.items = this.packageItemsData(customData, this.detailData);
          });
      });
    }
  },
  components: {
    InviteWechat,
    CustomGroup,
    SkuPopup,
    ShareBtn,
    BottomGoodsAction
  }
});
</script>

<style scoped>
.goods-detail {
  padding-bottom: 50px;
}
</style>
