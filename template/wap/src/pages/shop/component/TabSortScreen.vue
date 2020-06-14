<template>
  <div class="tab-sort-screen">
    <van-tabs @click="onSort" @disabled="onDisabled">
      <van-tab
        v-for="(item,index) in tabs"
        :key="index"
        v-if="item.isShow"
        :disabled="item.disabled"
      >
        <div slot="title">
          {{item.name=='全部'?shopGroupText:item.name}}
          <van-icon v-if="item.icon" :name="item.icon+' '+item.sort_type" />
        </div>
      </van-tab>
    </van-tabs>
    <van-popup
      v-model="groupPopupShow"
      position="top"
      class="shop-popup"
      :close-on-click-overlay="false"
      :overlay-style="overlayStyle"
    >
      <van-cell-group>
        <van-cell
          clickable
          title="全部"
          @click="onGroupSort(0,'')"
          :class="shopGroupActive === 0 ? 'active' : ''"
        />
        <van-cell
          clickable
          v-for="(item,index) in shopGroupList"
          :key="index"
          :title="item.group_name"
          @click="onGroupSort(index+1,item.shop_group_id)"
          :class="shopGroupActive === index+1 ? 'active' : ''"
        />
      </van-cell-group>
    </van-popup>
  </div>
</template>

<script>
import { isEmpty } from "@/utils/util";
import { GET_SHOPGROUP } from "@/api/shop";
export default {
  data() {
    return {
      groupPopupShow: false,
      tabs: [
        {
          name: "全部",
          sort: false,
          isShow: true
        },
        {
          name: "销量",
          icon: "v-icon-sort2",
          sort: "sale_num",
          sort_type: "DESC",
          isShow: !this.$store.state.config.addons.store
        },
        {
          name: "人气",
          icon: "v-icon-sort2",
          sort: "shop_collect",
          sort_type: "DESC",
          isShow: true
        },
        {
          name: "评分",
          icon: "v-icon-sort2",
          sort: "comprehensive",
          sort_type: "DESC",
          isShow: true
        },
        {
          name: "附近门店",
          disabled: true,
          sort: false,
          isShow: this.$store.state.config.addons.store
        }
      ],
      shopGroupList: [],
      shopGroupActive: 0
    };
  },
  computed: {
    overlayStyle() {
      return {
        zIndex: 1000
      };
    },
    shopGroupText() {
      let text = "全部";
      if (!isEmpty(this.shopGroupList && this.shopGroupActive != 0)) {
        text = this.shopGroupList[this.shopGroupActive - 1].group_name;
      }
      return text;
    }
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
      let current =
        index === 0
          ? 0
          : $this.$store.state.config.addons.store
          ? index + 1
          : index;

      if (current === 0) {
        if (isEmpty($this.shopGroupList)) {
          GET_SHOPGROUP().then(({ data }) => {
            $this.shopGroupList = data.shop_group_list.filter(
              ({ is_visible }) => is_visible
            );
          });
        }
        $this.groupPopupShow = !$this.groupPopupShow;
        return false;
      } else {
        $this.shopGroupActive = 0;
        $this.groupPopupShow = false;
      }
      params.page_index = 1;
      params.order = $this.tabs[current].sort;
      params.shop_group_id = "";
      if ($this.tabs[current].sort_type) {
        //升序降序
        params.sort = $this.tabs[current].sort_type;
        if ($this.tabs[current].sort_type == "DESC") {
          $this.tabs[current].sort_type = "ASC";
        } else {
          $this.tabs[current].sort_type = "DESC";
        }
      } else {
        // 默认
        params.sort = "";
      }

      if ($this.setParams) {
        $this.setParams(params);
      }
    },
    onDisabled() {
      this.$router.push("/store/list");
    },
    // 店铺分组排序
    onGroupSort(index, shop_group_id) {
      const $this = this;
      let params = $this.$parent.params;
      params.page_index = 1;
      params.order = "";
      params.sort = "";
      params.shop_group_id = shop_group_id;
      $this.shopGroupActive = index;
      $this.groupPopupShow = false;
      if ($this.setParams) {
        $this.setParams(params);
      }
    },

    // 是否显示关闭popup
    isShowPopup(flag) {
      if (flag === "tab") {
        this.groupPopupShow = true;
      } else {
        this.groupPopupShow = flag;
      }
    }
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
  top: 46px;
  width: 100%;
  z-index: 1002;
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

.tab-sort-screen >>> .van-hairline--top-bottom::after {
  border-top: none;
}

.shop-popup {
  top: 90px;
  z-index: 1001 !important;
}
.van-cell.active {
  color: #ff454e;
}
</style>
