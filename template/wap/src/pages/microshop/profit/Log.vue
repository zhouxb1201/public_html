<template>
  <div class="microshop-log bg-f8">
    <Navbar />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      @load="loadList"
    >
      <van-cell :value="item.create_time" v-for="(item,index) in list" :key="index">
        <div slot="title">
          <div :class="item.profit > 0 ? 'positive' : 'negative'">{{item.profit}}</div>
          <div class="fs-12 text-regular">{{item.text}}</div>
        </div>
      </van-cell>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { GET_MICROSHOPLOG } from "@/api/microshop";
import { list } from "@/mixins";
export default sfc({
  name: "microshop-log",
  data() {
    return {};
  },
  mixins: [list],
  mounted() {
    this.loadList();
  },
  methods: {
    loadList(init) {
      const $this = this;
      GET_MICROSHOPLOG($this.params)
        .then(({ data }) => {
          let list = data.data ? data.data : [];
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
.positive {
  color: #4b0;
}
.negative {
  color: #ff454e;
}
</style>
