<template>
  <div class="property-log bg-f8">
    <Navbar :title="navbarTitle" />
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      @load="loadList"
    >
      <van-cell
        :value="item.create_time"
        v-for="(item,index) in list"
        :key="index"
        :to="'/property/log/detail/'+item.id"
      >
        <div slot="title">{{item.type_name}}</div>
        <div
          slot="label"
          :class="isThroughClass(item.status)"
        >{{$store.state.member.memberSetText.balance_style}}： {{item.balance}}</div>
        <div>
          <div :class="moneyClass(item)">{{item.change_money}}</div>
          <div class="fs-12 text-regular">{{item.create_time}}</div>
        </div>
      </van-cell>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { GET_ASSETBALANCELOG } from "@/api/property";
import { isEmpty } from "@/utils/util";
import { list } from "@/mixins";
export default sfc({
  name: "property-log",
  data() {
    return {};
  },
  mixins: [list],
  mounted() {
    this.loadList();
  },
  computed: {
    navbarTitle() {
      const { balance_style } = this.$store.state.member.memberSetText;
      let title = balance_style + "明细";
      document.title = title;
      return title;
    }
  },
  methods: {
    moneyClass({ change_money, status }) {
      let num = parseFloat(change_money);
      let through = this.isThroughClass(status);
      return through || (num > 0 ? "positive" : "negative");
    },
    isThroughClass(state) {
      return state == -1 || state == 4 ? "text-through" : "";
    },
    loadList(init) {
      const $this = this;
      const isProceeds = $this.$route.hash == "#proceeds";
      GET_ASSETBALANCELOG($this.params, isProceeds)
        .then(({ data }) => {
          let list = data.data;
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

.text-through {
  text-decoration: line-through;
}
</style>
