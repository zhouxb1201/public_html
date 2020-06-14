<template>
  <div class="order-after bg-f8">
    <Navbar />
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
      <AfterOrderPanelGroup
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
import AfterOrderPanelGroup from "./component/AfterOrderPanelGroup";
import { GET_AFTERORDERLIST } from "@/api/order";
import { isEmpty } from "@/utils/util";
import { list } from "@/mixins";
export default {
  name: "order-after",
  data() {
    return {
      tab_active: 0,
      tabs: [
        {
          name: "全部",
          status: 0
        },
        {
          name: "待处理",
          status: 1
        },
        {
          name: "已打款",
          status: 2
        }
      ],

      params: {
        order_status: 0,
        search_text: ""
      }
    };
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
      this.params.search_text = text;
      this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_AFTERORDERLIST($this.params)
        .then(({ data }) => {
          let list = $this.packageListData(data.order_info);
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    },
    packageListData(list = []) {
      return list.map(e => {
        let obj = {};
        let item = [];
        let operate = [];
        e.order_item_list.forEach(g => {
          item.push({
            price: g.price,
            desc: g.sku_name,
            title: g.goods_name,
            img: g.picture,
            num: g.num,
            id: g.order_goods_id,
            value: g.status_name,
            operateText: g.no_operation,
            operate: g.member_operation
          });
        });
        obj.title = "订单编号：" + e.order_no;
        obj.value = "";
        obj.item = item;
        obj.operate = e.member_operation;
        obj.operateText = e.no_operation;
        obj.moneyText = "售后金额：";
        obj.money = e.refund_require_money;
        obj.id = e.order_id;
        return obj;
      });
    },
    onInitList(message) {
      this.$Toast.success(message);
      this.loadList("init");
    }
  },
  components: {
    HeadTab,
    AfterOrderPanelGroup
  }
};
</script>

<style scoped>
</style>
