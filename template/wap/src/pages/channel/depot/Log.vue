<template>
  <div class="channel-depot-log bg-f8">
    <Navbar />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      @load="loadList"
    >
      <van-cell-group>
        <van-cell class="item" v-for="(item,index) in list" :key="index">
          <van-row type="flex" justify="space-between">
            <van-col span="22" class="text-nowrap">{{item.buy_type | buy_type}}：{{item.goods_name}}</van-col>
            <van-col
              span="2"
              class="text-right"
              :class="item.buy_type == 1 ? 'color-success' : 'color-fail'"
            >{{item.buy_type == 1 ? '+' : '-'}}{{item.total_num}}</van-col>
          </van-row>
          <van-row type="flex" justify="space-between" class="fs-12 text-regular">
            <van-col span="14" class="text-nowrap">关联单号{{item.order_no}}</van-col>
            <van-col span="10" class="text-right">{{item.create_time | formatDate('s')}}</van-col>
          </van-row>
        </van-cell>
      </van-cell-group>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { GET_DEPOTLOG } from "@/api/channel";
import { list } from "@/mixins";
export default sfc({
  name: "channel-depot-log",
  data() {
    return {};
  },
  mixins: [list],
  filters: {
    buy_type(value) {
      let text = "";
      if (value == 1) {
        text = "采购";
      } else if (value == 2) {
        text = "自提";
      } else if (value == 3) {
        text = "出货";
      } else if (value == 4) {
        text = "零售";
      }
      return text;
    }
  },
  mounted() {
    this.loadList();
  },
  methods: {
    loadList(init) {
      const $this = this;
      GET_DEPOTLOG($this.params)
        .then(({ data }) => {
          let list = data.channel_goods_info ? data.channel_goods_info : [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  }
});
</script>
<style scoped>
.color-success {
  color: #4b0;
}

.color-fail {
  color: #f44;
}
</style>

