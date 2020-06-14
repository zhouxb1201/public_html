<template>
  <Layout ref="load" class="message-notice">
    <Navbar />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      :empty="{
        message: '暂无消息通知', 
        showFoot: false,
      }"
      @load="loadList"
    >
      <CellCardBox
        v-for="(item , index) in list"
        :key="index"
        :title="item.title"
        :content="item.content"
        :time="item.create_time"
      />
    </List>
  </Layout>
</template>

<script>
import { GET_THINGCIRCLEMESSAGE } from "@/api/thingcircle";
import { list } from "@/mixins";
import CellCardBox from "./component/CellCardBox";
import sfc from "@/utils/create";
export default sfc({
  name: "message-notice",
  data() {
    return {
      params: {
        page_index: 1,
        page_size: 10
      }
    };
  },
  mixins: [list],
  mounted() {
    this.$refs.load.success();
    this.loadList();
  },
  methods: {
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_THINGCIRCLEMESSAGE($this.params)
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
    CellCardBox
  }
});
</script>
<style scoped>
.item {
  margin: 10px;
}
.item h4 {
  text-align: center;
  font-size: 12px;
  font-weight: normal;
  color: #999;
}
.cell-group-box {
  position: relative;
  overflow: hidden;
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.05);
  word-break: break-all;
  padding: 10px;
  margin-top: 10px;
}
.title {
  padding: 8px 0px;
}
.text {
  line-height: 16px;
  color: #999;
  font-size: 12px;
}
</style>