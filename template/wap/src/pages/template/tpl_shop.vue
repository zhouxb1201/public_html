<template>
  <div :class="item.id">
    <div class="vui-shop">
      <div class="vui-shop-title">
        ——
        <span class="text">{{item.params.title}}</span>——
      </div>
      <div v-if="item.params.recommendtype == '0'">
        <div class="vui-shop-list" v-if="list.length > 0">
          <div class="item" v-for="(child,index) in filterList" :key="index">
            <img
              v-lazy="child.shop_logo"
              :key="child.shop_logo"
              pic-type="shop"
              @click="toShop(child.shop_id)"
            />
          </div>
        </div>
        <div v-else class="empty">{{text}}</div>
      </div>
      <div class="vui-shop-list" v-if="item.params.recommendtype == '1'">
        <div class="item" v-for="(child,index) in item.data" :key="index">
          <img
            v-lazy="child.pic_cover"
            :key="child.pic_cover"
            pic-type="shop"
            @click="toShop(child.shop_id)"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { GET_SHOPLIST } from "@/api/shop";
export default {
  name: "tpl_shop",
  data() {
    return {
      list: ""
    };
  },
  computed: {
    filterList() {
      let list = [];
      list = this.list.filter((e, i) => i < this.item.params.recommendnum);
      return list;
    },
    text() {
      let text = "";
      if (!this.$store.state.config.addons.shop) {
        text = "店铺应用未开启";
      } else if (this.list.length == 0) {
        text = "暂无店铺";
      }
      return text;
    },
    params() {
      const $this = this;
      const params = $this.item.params;
      let obj = {
        page_index: 1,
        order: "",
        sort: ""
      };
      if (params.recommendcondi == "0") {
        obj.order = "shop_create_time";
        obj.sort = "ASC";
      } else if (params.recommendcondi == "1") {
        obj.order = "shop_create_time";
        obj.sort = "DESC";
      } else if (params.recommendcondi == "2") {
        obj.order = "sale_num";
        obj.sort = "ASC";
      } else if (params.recommendcondi == "3") {
        obj.order = "sale_num";
        obj.sort = "DESC";
      } else if (params.recommendcondi == "4") {
        obj.order = "shop_collect";
        obj.sort = "ASC";
      } else if (params.recommendcondi == "5") {
        obj.order = "shop_collect";
        obj.sort = "DESC";
      }
      return obj;
    }
  },
  props: {
    type: [String, Number],
    item: Object
  },
  mounted() {
    const $this = this;
    const params = $this.params;
    if (
      $this.item.params.recommendtype == "0" &&
      $this.$store.state.config.addons.shop == 1
    ) {
      GET_SHOPLIST(params).then(({ data }) => {
        $this.list = data.shop_list;
      });
    }
  },
  methods: {
    toShop(shopid) {
      this.$router.push("/shop/home/" + shopid);
    }
  }
};
</script>

<style scoped>
.vui-shop-title {
  text-align: center;
  padding: 10px 0px;
  margin: 0 20px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.vui-shop-title .text {
  padding: 0 10px;
  font-weight: 800;
}

.vui-shop-list {
  display: -webkit-box;
  display: -webkit-flex;
  display: -ms-flexbox;
  display: flex;
  -webkit-box-orient: wrap;
  -webkit-flex-wrap: wrap;
  -ms-flex-wrap: wrap;
  flex-wrap: wrap;
  padding: 0 5px;
}

.vui-shop-list .item {
  display: block;
  width: 33.3333334%;
  text-align: center;
  margin-bottom: 6px;
  height: 50px;
}

.vui-shop-list .item img {
  width: 100%;
  height: auto;
  max-height: 100%;
  padding: 0 5px;
}
</style>
