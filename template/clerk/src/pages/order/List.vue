<template>
  <div class="order-list bg-f8">
    <Navbar/>
    <HeadTab
      v-model="tab_active"
      :tabs="tabs"
      @tab-change="onTab"
      search-placeholder="订单号/商品名称"
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
      :empty="{pageType:'order',message: '没有相关订单'}"
      @load="loadList"
    >
      <OrderPanelGroup
        v-for="(item,index) in list"
        :key="index"
        :items="item"
        @init-list="onInitList"
      />
    </List>
  </div>
</template>

<script>
import HeadTab from "@/components/HeadTab";
import OrderPanelGroup from "./component/OrderPanelGroup";
import { GET_ORDERLIST } from "@/api/order";
import { isEmpty } from "@/utils/util";
import { list } from "@/mixins";
export default {
  name: "order-list",
  data() {
    const status = !isEmpty(this.$route.query) ? this.$route.query.status : "";
    return {
      tab_active: 0,

      tabs: [
        {
          name: "全部",
          status: ""
        },
        {
          name: "待提货",
          status: 1
        },
        {
          name: "已提货",
          status: 3
        },
        {
          name: "已完成",
          status: 4
        }
      ],

      params: {
        order_status: status,
        search_text: ""
      }
    };
  },
  watch: {
    "$route.query.status": function(order_status) {
      if (order_status !== undefined) {
        this.params.order_status = order_status;
        this.params.search_text = "";
        this.loadList("init");
      }
    }
  },
  mixins: [list],
  created() {
    this.loadList();
  },
  methods: {
    onTab(index) {
      const $this = this;
      const status = $this.tabs[index].status;
      $this.params.order_status = status;
      $this.loadList("init");
    },
    onSearch(text) {
      const $this = this;
      this.params.search_text = text;
      $this.loadList("init");
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
};
</script>

<style scoped>
</style>
