<template>
  <div class="channel-depot-detail bg-f8">
    <Navbar />
    <HeadTab v-model="tab_active" :tabs="tabs" @tab-change="onTab" />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      @load="loadList"
    >
      <van-cell-group class="item card-group-box" v-for="(item,index) in list" :key="index">
        <van-cell
          class="text-nowrap"
          :title="params.tag_status | filterTitle(item.create_time_date)"
          :value="'数量：'+item.total_num"
        />
        <van-cell v-if="params.tag_status == 1">
          <div>采购单价：{{item.price | yuan}}</div>
          <div>折扣：{{item.channel_purchase_discount | rate}}</div>
          <div>商品售价：{{item.platform_price | yuan}}</div>
        </van-cell>
        <van-cell v-else-if="params.tag_status == 2">
          <div>出货单价：{{item.price | yuan}}</div>
          <div>采购单价：{{item.purchase_price | yuan}}</div>
          <div>商品售价：{{item.platform_price | yuan}}</div>
        </van-cell>
        <van-cell v-else-if="params.tag_status == 3">
          <div>采购单价：{{item.purchase_price | yuan}}</div>
          <div>折扣：{{item.channel_purchase_discount | rate}}</div>
          <div>商品售价：{{item.platform_price | yuan}}</div>
        </van-cell>
        <van-cell v-else-if="params.tag_status == 4">
          <div>采购单价：{{item.purchase_price | yuan}}</div>
          <div>零售单价：{{item.retail_price | yuan}}</div>
          <div>实收金额：{{item.real_money | yuan}}</div>
        </van-cell>
      </van-cell-group>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadTab from "@/components/HeadTab";
import { GET_DEPOTDETAIL } from "@/api/channel";
import { list } from "@/mixins";
export default sfc({
  name: "channel-depot-detail",
  data() {
    const sku_id = this.$route.params.skuid;
    return {
      tab_active: 0,
      tabs: [
        {
          name: "采购",
          type: 1
        },
        {
          name: "出货",
          type: 2
        },
        {
          name: "提货",
          type: 3
        },
        {
          name: "零售",
          type: 4
        }
      ],

      params: {
        page_index: 1,
        tag_status: 1,
        sku_id
      }
    };
  },
  mixins: [list],
  filters: {
    filterTitle(value, time) {
      let title = "";
      if (value == 1) {
        title = "采购时间";
      } else if (value == 2) {
        title = "出货时间";
      } else if (value == 3) {
        title = "提货时间";
      } else if (value == 4) {
        title = "零售时间";
      }
      return title + "：" + time;
    },
    rate(value) {
      return parseFloat(value) * 100 + "%";
    }
  },
  watch: {
    "$route.params.skuid": function(sku_id) {
      if (sku_id) {
        this.tab_active = 0;
        this.params.tag_status = 1;
        this.params.sku_id = sku_id;
        this.loadList("init");
      }
    }
  },
  mounted() {
    this.loadList();
  },
  methods: {
    onTab(index) {
      const $this = this;
      const type = $this.tabs[index].type;
      $this.params.tag_status = type;
      $this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_DEPOTDETAIL($this.params)
        .then(({ data }) => {
          let list = data.channel_goods_info ? data.channel_goods_info : [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  components: {
    HeadTab
  }
});
</script>
<style scoped>
</style>
