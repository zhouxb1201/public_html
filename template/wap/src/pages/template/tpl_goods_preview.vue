<template>
  <div :class="item.id">
    <div class="vui-goods-group" :class="itemClass" :style="itemStyle">
      <GoodsBox
        class="item e-handle"
        v-for="(child,index) in list"
        :key="index"
        :image="child.logo | BASESRC"
        :name="child.goods_name"
        :price="child.price"
      />
    </div>
  </div>
</template>

<script>
import GoodsBox from "@/components/GoodsBox";
export default {
  name: "tpl_goods_preview",
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
        sort: "",
        goods_type: params.goodstype
      };
      if ($this.item.shoptype == "2") {
        // 2 为属于店铺自营店
        obj.shop_id = $this.$route.params.shopid;
      }
      if (params.goodssort == "0") {
        obj.order = "create_time";
        obj.sort = "ASC";
      } else if (params.goodssort == "1") {
        obj.order = "create_time";
        obj.sort = "DESC";
      } else if (params.goodssort == "2") {
        obj.order = "sales";
        obj.sort = "ASC";
      } else if (params.goodssort == "3") {
        obj.order = "sales";
        obj.sort = "DESC";
      } else if (params.goodssort == "4") {
        obj.order = "collects";
        obj.sort = "ASC";
      } else if (params.goodssort == "5") {
        obj.order = "collects";
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
          price: "999"
        },
        {
          logo: "/public/platform/images/custom/default/goods-2.jpg",
          goods_name: "预览商品名称",
          price: "999"
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
    GoodsBox
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
