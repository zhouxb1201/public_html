<template>
  <div class="prize-list bg-f8">
    <Navbar :isMenu="false" />
    <HeadTab v-model="tab_active" :tabs="tabs" @tab-change="onTab" />
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{pageType:'goods',top:$store.state.isWeixin?46:90,message: '没有相关奖品'}"
      @load="loadList"
    >
      <Card v-for="(item,index) in list" :key="index" :items="item" />
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { GET_PRIZELIST } from "@/api/prize";
import Card from "./component/Card";
import HeadTab from "@/components/HeadTab";
import { list } from "@/mixins";
export default sfc({
  name: "prize-list",
  data() {
    return {
      tab_active: 0,
      tabs: [
        {
          name: "未领奖",
          state: 1
        },
        {
          name: "已领奖",
          state: 2
        },
        {
          name: "已过期",
          state: 3
        }
      ],
      params: {
        state: 1,
        page_index: 1,
        page_size: 10
      }
    };
  },
  mixins: [list],
  mounted() {
    this.loadList();
  },
  methods: {
    onTab(index) {
      const $this = this;
      const state = $this.tabs[index].state;
      $this.params.state = state;
      $this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_PRIZELIST($this.params).then(({ data }) => {
        let list = data.data;
        $this.pushToList(list, data.page_count, init);
        //console.log(data);
      });
    }
  },
  components: {
    Card,
    HeadTab
  }
});
</script>
<style scoped>
</style>


