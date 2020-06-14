<template>
  <Layout ref="load" class="mall-cart bg-f8">
    <Navbar :isMenu="false" />
    <div class="cart-list" v-if="items.length > 0">
      <CartPanelGroup
        :items="item"
        v-for="(item, index) in items"
        :key="index"
        :load-data="loadData"
        :isShowExpress="isShowExpress"
        ref="cartPanel"
      />
    </div>
    <Empty
      page-type="cart"
      bottom="100"
      btn-text="去逛逛"
      btn-link="/mall/index"
      v-else
    />
    <SubmitBar
      :style="isBottom"
      :price="totalPrice"
      :disabled="isDisabled"
      button-text="结算"
      @submit="bindMobile('onSubmit')"
    >
      <van-checkbox v-model="allChecked" class="all-check-btn">
        全选
      </van-checkbox>
    </SubmitBar>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import Empty from "@/components/Empty";
import SubmitBar from "@/components/SubmitBar";
import CartPanelGroup from "./component/CartPanelGroup";
import { GET_CARTLIST } from "@/api/mall";
import { isEmpty } from "@/utils/util";
import { _encode } from "@/utils/base64";
import { bindMobile } from "@/mixins";
export default sfc({
  name: "mall-cart",
  data() {
    return {
      items: [],
      cart_id_list: [],
      isShowExpress: {}
    };
  },
  mixins: [bindMobile],
  computed: {
    isBottom() {
      if (this.$store.state.tabbar.isShowTabbar)
        return {
          bottom: 50 + "px"
        };
    },
    isDisabled() {
      return !isEmpty(this.cart_id_list) ? false : true;
    },
    // 是否全选
    allChecked: {
      get: function() {
        // 选中的item组
        if (this.items.length > 0) {
          const checkedArr = this.items.map((e, i) => {
            const checkItems = e.goods_list.filter(item => {
              return item.checked == true && !item.disabled;
            }).length;
            const InitialItems = this.items[i].goods_list.filter(item => {
              return !item.disabled;
            }).length;
            return checkItems == InitialItems;
          });
          return (
            checkedArr.filter(item => {
              return item == true;
            }).length == checkedArr.length
          );
        } else {
          return false;
        }
      },
      set: function(value) {
        this.items.forEach(item => {
          item.checked = value;
          item.goods_list.forEach(g => {
            g.checked = g.disabled ? false : value;
          });
        });
      }
    },
    // 计算总价
    totalPrice() {
      const $this = this;
      const items = $this.items;
      let total = 0;
      let arr = [];
      let sku_list = [];
      items.forEach(({ goods_list }) => {
        goods_list.forEach(item => {
          if (item.checked) {
            arr.push(item.cart_id);
            sku_list.push({
              sku_id: item.sku_id,
              coupon_id: 0,
              shop_id: item.shop_id
            });
            total += parseFloat(item.price) * parseFloat(item.num);
          }
        });
      });
      $this.cart_id_list = arr;
      $this.sku_list = sku_list;
      return total;
    }
  },
  mounted() {
    this.loadData();
  },
  methods: {
    loadData(index) {
      const $this = this;
      GET_CARTLIST({
        page_index: 1
      })
        .then(({ data, code, message }) => {
          $this.isShowExpress.has_express = data.has_express;
          $this.isShowExpress.has_store = data.has_store;
          const items = data.shop_info || [];
          $this.items = items.map(item => {
            const originItem = $this.items.filter(
              ({ shop_id }) => shop_id == item.shop_id
            )[0];
            let obj = {};
            obj.shop_id = item.shop_id;
            obj.shop_name = item.shop_name;
            obj.checked = originItem ? originItem.checked : true;
            obj.goods_list = item.goods_list;
            obj.goods_list.forEach(goodsItem => {
              const originGoodsItem = originItem
                ? originItem.goods_list.filter(
                    ({ goods_id }) => goods_id == goodsItem.goods_id
                  )[0]
                : "";
              if (
                (goodsItem.promotion_type && goodsItem.promotion_type != 5) ||
                !goodsItem.stock ||
                !goodsItem.state
              ) {
                goodsItem.disabled = true;
              } else {
                goodsItem.checked = originGoodsItem
                  ? originGoodsItem.checked
                  : true;
              }
            });
            obj.discount_info = item.discount_info;
            obj.mansong_info = item.mansong_info;
            if (obj.mansong_info.discount) {
              obj.mansong_info.discount = parseFloat(
                item.mansong_info.discount
              );
            }
            return obj;
          });
          if (code == 3) {
            $this.$Toast(message);
          }

          $this.$refs.load.success();
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    onSubmit() {
      const $this = this;
      const params = {};
      params.order_tag = "cart";
      params.cart_from = 1;
      params.cart_id_list = $this.cart_id_list;
      params.sku_list = $this.sku_list;
      // return console.log(params)
      $this.$router.push({
        name: "order-confirm",
        query: {
          params: _encode(JSON.stringify(params))
        }
      });
    }
  },
  components: {
    Empty,
    SubmitBar,
    CartPanelGroup
  }
});
</script>

<style scoped>
.mall-cart {
  min-height: calc(100vh - 100px) !important;
}

.cart-list {
  margin-bottom: 60px;
}

.all-check-btn {
  padding-left: 15px;
}
</style>
