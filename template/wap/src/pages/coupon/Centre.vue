<template>
  <div class="coupon-centre bg-f8">
    <Navbar />
    <HeadBanner :src="$BASEIMGPATH+'coupon-adv.png'" />
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{pageType:'coupon'}"
      @load="loadList"
    >
      <CentreItem v-for="(item,index) in list" :key="index" :items="item" :load-data="loadList" />
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadBanner from "@/components/HeadBanner";
import CentreItem from "./component/CentreItem";
import { GET_COUPONCENTRE } from "@/api/coupon";
import { list } from "@/mixins";
export default sfc({
  name: "coupon-centre",
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
      if (init && init === "init") {
        $this.initList();
      }
      GET_COUPONCENTRE($this.params)
        .then(({ data }) => {
          let list = data.list;
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  components: {
    HeadBanner,
    CentreItem
  }
});
</script>

<style scoped>
</style>
