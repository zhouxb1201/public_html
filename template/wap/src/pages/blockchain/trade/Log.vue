<template>
  <Layout ref="load" class="blockchain-trade-log bg-f8">
    <Navbar />
    <HeadTab v-model="tab_active" :tabs="tabs" @tab-change="onTab" />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      @load="loadList"
    >
      <van-cell v-for="(item,index) in list" :key="index" clickable :to="toDetail(item.id)">
        <div slot="title">
          <span :class="countClassName(item.count)">{{item.count}}</span>
          <span class="line">|</span>
          <span :class="item.status == 1 ? 'positive' : 'negative'">{{item.status_name}}</span>
        </div>
        <div slot="label">{{item.type_name}}</div>
        <div class="fs-12">{{item.ask_for_date}}</div>
      </van-cell>
    </List>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import HeadTab from "@/components/HeadTab";
import { GET_BLOCKCHAINLOGLIST } from "@/api/blockchain";
import { list } from "@/mixins";
import blockchain from "../mixin";
export default sfc({
  name: "blockchain-trade-log",
  data() {
    const type = this.$route.params.type == "eth" ? 1 : 2;
    return {
      tab_active: 0,
      tabs: [
        { name: "进行中", type: 0 },
        { name: "已完成", type: 1 },
        { name: "失败", type: 2 }
      ],
      params: {
        status: 0,
        coin_type: type
      }
    };
  },
  mixins: [blockchain, list],
  methods: {
    onTab(index) {
      const $this = this;
      const type = $this.tabs[index].type;
      $this.params.status = type;
      $this.loadList("init");
    },
    loadData(data) {
      this.loadList();
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_BLOCKCHAINLOGLIST($this.params)
        .then(({ data }) => {
          let list = data.data ? data.data : [];
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    },
    countClassName(e) {
      return parseFloat(e) > 0 ? "positive" : "negative";
    },
    toDetail(id) {
      return {
        name: "blockchain-trade-detail",
        params: {
          type: this.$route.params.type,
          id
        }
      };
    }
  },
  components: {
    HeadTab
  }
});
</script>

<style scoped>
.line {
  padding: 0 4px;
}
.positive {
  color: #4b0;
}
.negative {
  color: #ff454e;
}
</style>
