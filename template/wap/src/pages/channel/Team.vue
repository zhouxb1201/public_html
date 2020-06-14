<template>
  <div class="channel-team bg-f8">
    <Navbar />
    <List
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      @load="loadList"
    >
      <van-cell-group>
        <van-cell class="item" v-for="(item,index) in list" :key="index">
          <div class="name">{{item.name}}</div>
          <van-row type="flex" class="fs-12 text-regular">
            <van-col span="10">
              上级：
              <span>{{item.up_channel_name}}</span>
            </van-col>
            <van-col span="14">
              推荐人：
              <span>{{item.referee_name}}</span>
            </van-col>
          </van-row>
          <van-row type="flex" class="fs-12 text-regular">
            <van-col span="10">
              等级：
              <span>{{item.grade_name}}</span>
            </van-col>
            <van-col span="14">
              下级：
              <span>{{item.down_channel_num}}</span>人
            </van-col>
          </van-row>
        </van-cell>
      </van-cell-group>
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { GET_TEAMLIST } from "@/api/channel";
import { list } from "@/mixins";
export default sfc({
  name: "channel-team",
  data() {
    return {};
  },
  mounted() {
    this.loadList();
  },
  mixins: [list],
  methods: {
    loadList(init) {
      const $this = this;
      if (init && init === "init") {
        $this.initList();
      }
      GET_TEAMLIST($this.params)
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
</style>
