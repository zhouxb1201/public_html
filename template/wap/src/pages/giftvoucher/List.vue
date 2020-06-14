<template>
  <div class="giftvoucher-list bg-f8">
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
      <InfoItem v-for="(item,index) in list" :key="index" :items="item">
        <router-link
          class="a-link e-handle fs-12"
          slot="foot"
          :to="'/giftvoucher/detail/'+item.record_id"
        >详情 ▶</router-link>
      </InfoItem>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import HeadTab from "@/components/HeadTab";
import InfoItem from "./component/InfoItem";
import { GET_GIFTVOUCHERLIST } from "@/api/giftvoucher";
import { list } from "@/mixins";
export default sfc({
  name: "giftvoucher-list",
  data() {
    return {
      tab_active: 0,
      tabs: [
        {
          name: "未使用",
          status: 1
        },
        {
          name: "已使用",
          status: 2
        },
        {
          name: "已过期",
          status: 3
        }
      ],
      params: {
        state: 1
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
      GET_GIFTVOUCHERLIST($this.params)
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
    InfoItem
  }
});
</script>

<style scoped>
</style>
