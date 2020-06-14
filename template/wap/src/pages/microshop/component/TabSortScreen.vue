<template>
<div class="tab-sort-screen">
  <van-tabs @click="onSort" @disabled="isShowPopup('tab')">
    <van-tab v-for="(item,index) in tab" :key="index" :disabled="item.sort === false">
      <div slot="title">
        {{item.name}}
        <van-icon v-if="item.icon" :name="item.icon+' '+item.sort_type" />
      </div>
    </van-tab>
  </van-tabs>
  <PopupScreen :show="screenPopupShow" @popup="isShowPopup" @screen="onScreen" />
</div>
</template>
<script>
import PopupScreen from "./PopupScreen";
export default {
  data() {
    return {
      screenPopupShow: false,
      tab: [
        {
          name: "默认",
          sort: ""
        },
        {
          name: "销售量",
          icon: "v-icon-sort2",
          sort: "sales",
          sort_type: "DESC"
        },
        {
          name: "价格",
          icon: "v-icon-sort2",
          sort: "price",
          sort_type: "DESC"
        },
        {
          name: "筛选",
          icon: "v-icon-screen",
          sort: false
        }
      ],
      params: {
        page_index: 1,
        page_size: 10,
        order: "",
        sort: "",
        min_price: "",
        max_price: "",
        free_shipping_fee: "",
        new_goods: "",
        goods_type: "",
        search_text: this.$route.query.search_text
          ? this.$route.query.search_text
          : "",
        category_id: this.$route.query.category_id
          ? this.$route.query.category_id
          : "",
        microshop_type:1  
      }
    };
  },
  props: {
    setParams: {
      type: Function,
      default: null
    }
  },
  methods: {
    // 商品排序
    onSort(index) {
      const $this = this;
      let params = $this.$parent.params;
      params.page_index = 1;
      params.order = $this.tab[index].sort;
      if ($this.tab[index].sort_type) {
        //升序降序
        params.sort = $this.tab[index].sort_type;
        if ($this.tab[index].sort_type == "DESC") {
          $this.tab[index].sort_type = "ASC";
        } else {
          $this.tab[index].sort_type = "DESC";
        }
      } else {
        // 默认
        params.sort = "";
      }

      if ($this.setParams) {
        $this.setParams(params, "init");
      }
    },

    // 商品筛选
    onScreen(data) {
      const $this = this;
      let params = $this.$parent.params;
      params.page_index = 1;
      params.free_shipping_fee = data.free_shipping_fee;
      params.max_price = data.max_price;
      params.min_price = data.min_price;
      params.new_goods = data.new_goods;
      $this.isShowPopup(false);
      if ($this.setParams) {
        $this.setParams(params, "init");
      }
    },

    // 是否显示关闭popup
    isShowPopup(flag) {
      if (flag === "tab") {
        this.screenPopupShow = true;
      } else {
        this.screenPopupShow = flag;
      }
    }
  },
  components: {
    PopupScreen
  }
};
</script>
<style scoped>
.tab-sort-screen {
  height: 44px;
  background: #fff;
}
.van-tabs--line {
  padding-top: 44px;
  position: fixed;
  left: 0;
  top: inherit;
  width: 100%;
  z-index: 998;
}
.tab-sort-screen >>> .van-tabs__line {
  display: none;
}

.tab-sort-screen >>> .van-icon {
  font-size: 12px;
  font-weight: 800;
  color: #666;
}

.tab-sort-screen >>> .van-icon.van-icon-v-icon-sort2.ASC {
  transform: rotate(0deg);
}

.tab-sort-screen >>> .van-icon.van-icon-v-icon-sort2.DESC {
  transform: rotate(180deg);
}

.tab-sort-screen >>> .van-tab--disabled {
  color: #333;
}

.tab-sort-screen >>> .van-tab--active .van-icon {
  color: inherit;
}
</style>
