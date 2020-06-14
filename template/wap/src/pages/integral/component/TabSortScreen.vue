<template>
  <div class="tab-sort-screen">
    <van-tabs @click="onSort">
      <van-tab v-for="(item,index) in tab" :key="index" :disabled="item.sort === false">
        <div slot="title">
          {{item.name}}
          <van-icon v-if="item.icon" :name="item.icon+' '+item.sort_type" />
        </div>
      </van-tab>
    </van-tabs>
  </div>
</template>
<script>
export default {
  data() {
    const pointText = this.$store.state.member.memberSetText.point_style;
    return {
      tab: [
        {
          name: "默认",
          sort: ""
        },
        {
          name: "兑换量",
          icon: "v-icon-sort2",
          sort: "sales",
          sort_type: "DESC"
        },
        {
          name: pointText,
          icon: "v-icon-sort2",
          sort: "point_exchange",
          sort_type: "DESC"
        }
      ],
      params: {
        page_index: 1,
        page_size: 10,
        order: "",
        sort: "",
        search_text: this.$route.query.search_text
          ? this.$route.query.search_text
          : "",
        category_id: this.$route.query.category_id
          ? this.$route.query.category_id
          : "",
        shop_id: this.$route.query.shop_id ? this.$route.query.shop_id : ""
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
