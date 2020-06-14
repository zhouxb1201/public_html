<template>
  <div class="verify-log bg-f8">
    <Navbar />
    <HeadTab
      v-model="tab_active"
      :tabs="tabs"
      @tab-change="onTab"
      search-placeholder="订单编号 / 消费卡号 / 礼品券号 / 商品名称"
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
      <OrderPanelGroup v-for="(item,index) in list" :key="index" :items="item" />
    </List>
  </div>
</template>

<script>
import HeadTab from "@/components/HeadTab";
import OrderPanelGroup from "./component/OrderPanelGroup";
import { GET_VERIFYLOG } from "@/api/verify";
import { isEmpty } from "@/utils/util";
import { list } from "@/mixins";
export default {
  name: "verify-log",
  data() {
    return {
      tab_active: 0,
      tabs: [
        {
          name: "全部",
          status: 0
        },
        {
          name: "订单",
          status: 1
        },
        {
          name: "礼品券",
          status: 2
        },
        {
          name: "消费卡",
          status: 3
        }
      ],

      params: {
        status: 0,
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
      $this.params.status = status;
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
      GET_VERIFYLOG($this.params)
        .then(({ data }) => {
          let list = $this.packageListData(data.data);
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
    },
    packageListData(list = []) {
      return list.map(e => {
        let obj = {};
        let title = "";
        let value = "";
        let no = "";
        let item = [];
        let operate = [];
        if (e.type == 1) {
          title = "订单编号：" + e.order_no;
          e.order_item_list.forEach((g, i) => {
            item.push({
              id: g.goods_id,
              img: g.picture,
              title: g.goods_name,
              desc: g.sku_name,
              price: g.price,
              num: g.num
            });
          });
          operate.push({
            no: "detail",
            name: "订单详情"
          });
        } else if (e.type == 2) {
          title = "礼品券号：" + e.gift_voucher_code;
          item.push({
            img: e.gift_picture,
            title: e.giftvoucher_name,
            num: e.num
          });
        } else if (e.type == 3) {
          title = "消费卡号：" + e.card_code;
          item.push({
            img: e.card_picture,
            title: e.goods_name,
            num: e.num
          });
        }
        obj.title = title;
        obj.value = "核销员：" + e.assistant_name;
        obj.item = item;
        obj.operate = operate;
        obj.id = e.order_id;
        return obj;
      });
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
