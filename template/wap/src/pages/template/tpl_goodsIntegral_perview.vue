<template>
  <div :class="item.id">
    <div class="vui-goods-group" :class="itemClass" :style="itemStyle">
      <GoodsTab
        class="item e-handle"
        v-for="(child,index) in list"
        :key="index"
        :id="child.goods_id"
        :gtype="child.type"
        :exchange="child.point_exchange"
        :name="child.goods_name"
        :image="child.logo"
        :price="child.price"
      />
    </div>
  </div>
</template>

<script>
import GoodsTab from "../integral/component/GoodsTab";
export default {
  name: "tpl_goodsIntegral_preview",
  data() {
    return {
      list: []
    };
  },
  props: {
    type: [String, Number],
    item: Object
  },
  computed: {
    itemClass() {
      return "goods-group-" + this.item.params.showtype;
    },
    itemStyle() {
      return {
        background: this.item.style.background
      };
    },
    params() {
      const $this = this;
      const params = $this.item.params;
      let obj = {
        page_index: 1,
        page_size: 30,
        order: "",
        sort: ""
      };
      // if ($this.item.shoptype == "2") {
      //   // 2 为属于店铺自营店
      //   obj.shop_id = $this.$route.params.shopid;
      // }
      if (params.goodssort == "0") {
        obj.order = "sale_date";
        obj.sort = "ASC";
      } else if (params.goodssort == "1") {
        obj.order = "sale_date";
        obj.sort = "DESC";
      } else if (params.goodssort == "2") {
        obj.order = "sales";
        obj.sort = "ASC";
      } else if (params.goodssort == "3") {
        obj.order = "sales";
        obj.sort = "DESC";
      } else if (params.goodssort == "4") {
        obj.order = "point_exchange";
        obj.sort = "ASC";
      } else if (params.goodssort == "5") {
        obj.order = "point_exchange";
        obj.sort = "DESC";
      }
      return obj;
    }
  },
  mounted() {
    const $this = this;
    if ($this.item.params.recommendtype == "0") {
      $this.list = [
        {
          logo: "/public/platform/images/custom/default/goods-1.jpg",
          goods_name: "预览商品名称",
          price: "999",
          type: "商品",
          point_exchange: "22"
        },
        {
          logo: "/public/platform/images/custom/default/goods-2.jpg",
          goods_name: "预览商品名称",
          price: "999",
          type: "商品",
          point_exchange: "22"
        }
      ];
    } else {
      if ($this.item.data) {
        for (let i in $this.item.data) {
          $this.item.data[i].logo = $this.item.data[i].pic_cover_mid;
          $this.list.push($this.item.data[i]);
        }
      }
    }
  },
  components: {
    GoodsTab
  }
};
</script>

<style scoped>
.vui-goods-group {
  height: auto;
  overflow: hidden;
  background: #f3f3f3;
  padding: 4px;
}

.vui-goods-group.goods-group-1 .item {
  width: calc(100% - 8px);
  font-size: 16px;
}

.vui-goods-group.goods-group-2 .item {
  width: calc(50% - 8px);
  font-size: 14px;
}

.vui-goods-group.goods-group-3 .item {
  width: calc(33.33334% - 8px);
  font-size: 12px;
}
</style>
