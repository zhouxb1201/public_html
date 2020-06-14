<template>
  <div class="order-list bg-f8">
    <Navbar />
    <HeadTab
      v-model="active"
      :tabs="tabs"
      @tab-change="onTab"
      search-placeholder="订单号/店铺名称/商品名称"
      show-search
      :search-text="params.search_text"
      @search="onSearch"
    />
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{
        pageType: 'order',
        message: '没有相关订单',
        showFoot: true,
        btnLink: '/goods/list',
        btnText: '去购物'
      }"
      @load="loadList"
    >
      <OrderPanelGroup
        v-for="(item, index) in list"
        :key="index"
        :items="item"
        @init-list="onInitList"
      />
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadTab from "@/components/HeadTab";
import OrderPanelGroup from "./component/OrderPanelGroup";
import { GET_ORDERLIST } from "@/api/order";
import { isEmpty } from "@/utils/util";
import { list } from "@/mixins";
export default sfc({
  name: "order-list",
  data() {
    return {
      active: 0,
      tabs: [
        {
          name: "全部",
          status: ""
        },
        {
          name: "待付款",
          status: 0
        },
        {
          name: "待发货",
          status: 1
        },
        {
          name: "待收货",
          status: 2
        },
        {
          name: "待评价",
          status: -2
        },
        {
          name: "售后",
          status: -1
        }
      ]
    };
  },
  mixins: [list],
  created() {
    this.isFirstEnter = true;
  },
  beforeRouteEnter(to, from, next) {
    let arr = [
      "order-detail",
      "order-evaluate",
      "order-logistics",
      "order-post",
      "consumercard-list",
      "pay-payment"
    ];
    if (arr.indexOf(from.name) != -1) {
      to.meta.isBack = true;
    }
    next();
  },
  mounted() {
    // this.loadList();
  },
  activated() {
    if (!this.$route.meta.isBack || this.isFirstEnter) {
      this.params = Object.assign(this.params, { ...this.getParams() });
      this.active = this.getTabActive();
      this.loadList("init");
    }
    this.$route.meta.isBack = false;
    this.isFirstEnter = false;
  },
  methods: {
    getParams() {
      const status =
        this.$route.query.status == undefined ? "" : this.$route.query.status;
      return {
        order_status: status,
        search_text: ""
      };
    },
    getTabActive() {
      const query = this.$route.query;
      let index = 0;
      if (!isEmpty(query)) {
        const status = parseInt(query.status);
        if (!isNaN(status)) {
          if (status === 0) {
            index = 1;
          } else if (status === 1) {
            index = 2;
          } else if (status === 2) {
            index = 3;
          } else if (status === -2) {
            index = 4;
          } else if (status === -1) {
            index = 5;
          }
        }
      }
      return index;
    },
    onTab(index) {
      const status = this.tabs[index].status;
      this.params.order_status = status;
      this.$router.replace({ name: "order-list", query: { status } });
      this.loadList("init");
    },
    onSearch(text) {
      this.params.search_text = text;
      this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_ORDERLIST($this.params)
        .then(({ data }) => {
          let list = data.order_list.map(e => {
            e.order_goods = e.order_item_list;
            return e;
          });
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    },
    onInitList({ message }) {
      const $this = this;
      $this.$Toast.success({
        message,
        forbidClick: true
      });
      setTimeout(() => {
        $this.loadList("init");
      }, 500);
    }
  },
  components: {
    HeadTab,
    OrderPanelGroup
  }
});
</script>

<style scoped></style>
