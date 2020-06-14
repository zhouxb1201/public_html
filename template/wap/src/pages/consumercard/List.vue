<template>
  <div class="consumercard-list bg-f8">
    <Navbar />
    <HeadTab v-model="tab_active" :tabs="tabs" @tab-change="onTab" />
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{top:$store.state.isWeixin?46:90}"
      @load="loadList"
    >
      <ListItem :item="item" v-for="(item,index) in list" :key="index" />
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadTab from "@/components/HeadTab";
import ListItem from "./component/ListItem";
import { GET_CONSUMERCARDLIST } from "@/api/consumercard";
import { list } from "@/mixins";
export default sfc({
  name: "consumercard-list",
  data() {
    return {
      tab_active: 0,
      tabs: [
        {
          name: "可使用",
          status: 0
        },
        {
          name: "已使用",
          status: 1
        },
        {
          name: "已过期",
          status: 2
        }
      ],
      params: {
        state: 0
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
      const status = $this.tabs[index].status;
      $this.params.state = status;
      $this.loadList("init");
    },
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_CONSUMERCARDLIST($this.params)
        .then(({ data }) => {
          let list = data.data;
          $this.pushToList(list, data.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  },
  components: {
    HeadTab,
    ListItem
  }
});
</script>
<style scoped>
</style>
