<template>
  <div class="channel-order-list bg-f8">
    <Navbar :title="navbarTitle" />
    <HeadTab
      v-model="tab_active"
      :tabs="tabs"
      @tab-change="onTab"
      search-placeholder="订单号/店铺名称/商品名称"
      show-search
      :search-text="params.search_text"
      @search="onSearch"
    />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{pageType:'order',message: '没有相关订单'}"
      @load="loadList"
    >
      <OrderPanelGroup
        :items="item"
        v-for="(item,index) in list"
        :key="index"
        @callback="onInitList"
      />
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadTab from "@/components/HeadTab";
import OrderPanelGroup from "../component/OrderPanelGroup";
import { GET_ORDERLIST } from "@/api/channel";
import { list } from "@/mixins";
export default sfc({
  name: "channel-order-list",
  data() {
    const buy_type = this.$route.params.type;
    return {
      tab_active: 0,

      params: {
        page_index: 1,
        buy_type,
        order_status: "",
        search_text: ""
      }
    };
  },
  watch: {
    "$route.params.type": function(type) {
      if (
        this.$route.name == "channel-order-list" &&
        type &&
        this.buy_type !== type
      ) {
        this.tab_active = 0;
        this.params.buy_type = type;
        this.params.order_status = "";
        this.params.search_text = "";
        this.loadList("init");
      }
    }
  },
  mixins: [list],
  computed: {
    navbarTitle() {
      const type = this.$route.params.type;
      let title = "";
      if (type == "purchase") {
        title = "采购订单";
      } else if (type == "pickupgoods") {
        title = "提货订单";
      } else if (type == "output") {
        title = "出货订单";
      } else if (type == "retail") {
        title = "零售订单";
      }
      if (title) document.title = title;
      return title;
    },
    tabs() {
      const type = this.$route.params.type;
      let obj = {
        purchase: [
          {
            name: "全部",
            state: ""
          },
          {
            name: "待付款",
            state: 0
          },
          {
            name: "已完成",
            state: 4
          }
        ],
        pickupgoods: [
          {
            name: "全部",
            state: ""
          },
          {
            name: "待付款",
            state: 0
          },
          {
            name: "待发货",
            state: 1
          },
          {
            name: "已发货",
            state: 2
          },
          {
            name: "已收货",
            state: 3
          },
          {
            name: "已完成",
            state: 4
          },
          {
            name: "售后",
            state: -1
          }
        ],
        output: [
          {
            name: "全部",
            state: ""
          },
          {
            name: "待付款",
            state: 0
          },
          {
            name: "已发货",
            state: 2
          }
        ],
        retail: [
          {
            name: "全部",
            state: ""
          },
          {
            name: "待付款",
            state: 0
          },
          {
            name: "待发货",
            state: 1
          },
          {
            name: "已发货",
            state: 2
          },
          {
            name: "已收货",
            state: 3
          },
          {
            name: "已完成",
            state: 4
          },
          {
            name: "售后",
            state: -1
          }
        ]
      };
      return obj[type];
    }
  },
  mounted() {
    this.loadList();
  },
  methods: {
    onSearch(text) {
      this.params.search_text = text;
      this.loadList("init");
    },
    onTab(index) {
      const $this = this;
      const state = $this.tabs[index].state;
      $this.params.order_status = state;
      $this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_ORDERLIST($this.params)
        .then(({ data }) => {
          let list = data.data ? data.data : [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    },
    onInitList({ message }) {
      const $this = this;
      $this.$Toast.success(message);
      setTimeout(() => {
        $this.loadList("init");
      }, 1000);
    }
  },
  components: {
    HeadTab,
    OrderPanelGroup
  }
});
</script>
<style scoped>
</style>

