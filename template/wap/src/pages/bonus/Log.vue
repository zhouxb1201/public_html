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
          <div :class="item.bonus > 0 ? 'positive' : 'negative'">{{item.bonus}}</div>
          <div class="fs-12 text-regular">{{item.text}}</div>
        </div>
      </van-cell>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { GET_BONUSLOG } from "@/api/bonus";
import { list } from "@/mixins";
export default sfc({
  name: "bonus-log",
  data() {
    return {};
  },
  mixins: [list],
  computed: {
    navbarTitle() {
      const { bonus_details } = this.$store.state.member.bonusSetText.common;
      let title = bonus_details;
      document.title = title;
      return title;
    }
  },
  activated() {
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
  },
  mounted() {
    this.loadList();
  },
  methods: {
    loadList(init) {
      const $this = this;
      GET_BONUSLOG($this.params)
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
</style>
