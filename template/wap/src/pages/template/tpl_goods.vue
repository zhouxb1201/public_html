<template>
  <div :class="item.id">
    <div class="vui-goods-group" :class="itemClass" :style="itemStyle">
      <GoodsBox
        class="item e-handle"
        v-for="(child,index) in list"
        :key="index"
        :id="child.goods_id"
        :image="child.logo"
        :name="child.goods_name"
        :price="child.price"
      />
    </div>
  </div>
</template>

<script>
import GoodsBox from "@/components/GoodsBox";
import { GET_GOODSLIST, GET_GOODSCUSTOMLIST } from "@/api/goods";
export default {
  name: "tpl_goods",
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
      if (params.goodstype == "2" || $this.item.shoptype == "2") {
        /**
         * goodstype == 2 为属于店铺商品
         * shoptype == 2 为店铺类型
         */
        obj.shop_id = $this.$route.params.shopid
          ? $this.$route.params.shopid
          : params.shop_id || 0;
      }
      if (obj.shop_id != 0 && $this.item.shoptype == "2") {
        obj.goods_type = 2;
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
      GET_GOODSLIST($this.params).then(({ data }) => {
        $this.list = data.goods_list.filter(
          (e, i) => i < this.item.params.recommendnum
        );
      });
    } else {
      if ($this.item.data) {
        let arr = [];
        let goodsids = "";
        for (let i in $this.item.data) {
          arr.push($this.item.data[i].goods_id);
        }
        goodsids = arr.join(",");
        GET_GOODSCUSTOMLIST(goodsids).then(({ data }) => {
          $this.list = data;
        });
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
