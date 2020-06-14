<template>
  <div class="commission-log bg-f8">
    <Navbar :title="navbarTitle" />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      @load="loadList"
    >
      <van-cell :value="item.create_time" v-for="(item,index) in list" :key="index">
        <div slot="title">
          <div :class="item.commission > 0 ? 'positive' : 'negative'">{{item.commission}}</div>
          <div class="fs-12 text-regular">{{item.text}}</div>
        </div>
      </van-cell>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { GET_COMMISSIONLOG } from "@/api/commission";
import { list } from "@/mixins";
export default sfc({
  name: "commission-log",
  data() {
    return {};
  },
  mixins: [list],
  computed: {
    navbarTitle() {
      const { commission_details } = this.$store.state.member.commissionSetText;
      let title = commission_details;
      document.title = title;
      return title;
    }
  },
  mounted() {
    this.loadList();
  },
  activated() {
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
  },
  methods: {
    loadList(init) {
      const $this = this;
      GET_COMMISSIONLOG($this.params)
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
